<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\UserService;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Get all available services
     * 
     * Returns a list of all available services in the AREA platform,
     * including their connection status for the authenticated user.
     *
     * @group Services
     * @authenticated
     * 
     * @response 200 {
     *   "services": [
     *     {
     *       "id": 1,
     *       "name": "google",
     *       "description": "Google Workspace services (Gmail, Calendar, Drive)",
     *       "icon": "https://www.google.com/favicon.ico",
     *       "color": "#4285F4",
     *       "category": "productivity",
     *       "is_connected": true,
     *       "requires_auth": true
     *     },
     *     {
     *       "id": 2,
     *       "name": "timer",
     *       "description": "Schedule actions based on time intervals",
     *       "icon": "⏰",
     *       "color": "#FF6B6B",
     *       "category": "utility",
     *       "is_connected": false,
     *       "requires_auth": false
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Récupérer tous les services actifs
        $services = Service::where('is_active', true)->get();
        
        // Vérifier quels services sont connectés ET ont un token valide
        $userServices = UserService::where('user_id', $user->id)->get();
        $validConnectedServiceIds = $userServices->filter(function ($userService) {
            // Si pas d'expiration définie, le service est connecté
            if (is_null($userService->expires_at)) {
                return true;
            }
            // expires_at est déjà un Carbon grâce au cast, on utilise isPast() au lieu de isFuture()
            // car on veut exclure les tokens expirés
            $isValid = !$userService->expires_at->isPast();
            \Log::info("[ServiceController] Service {$userService->service_id}: expires=" . $userService->expires_at->toDateTimeString() . ", now=" . now()->toDateTimeString() . ", valid=" . ($isValid ? 'OUI' : 'NON'));
            return $isValid;
        })->pluck('service_id')->toArray();
        
        $servicesWithStatus = $services->map(function ($service) use ($validConnectedServiceIds) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description ?? $this->getServiceDescription($service->name),
                'icon' => $service->icon ?? $this->getServiceIcon($service->name),
                'color' => $this->getServiceColor($service->name),
                'category' => $this->getServiceCategory($service->name),
                'is_connected' => in_array($service->id, $validConnectedServiceIds),
                'requires_auth' => $this->requiresAuth($service->name),
            ];
        });
        
        return response()->json(['services' => $servicesWithStatus], 200);
    }

    /**
     * Get service details with available triggers and actions
     * 
     * Returns detailed information about a specific service including
     * all available triggers (actions that can start an automation)
     * and actions (reactions that can be executed).
     *
     * @group Services
     * @authenticated
     * 
     * @urlParam serviceId integer required The ID of the service. Example: 1
     * 
     * @response 200 {
     *   "service": {
     *     "id": 1,
     *     "name": "google",
     *     "description": "Google Workspace services",
     *     "triggers": [
     *       {
     *         "id": "new_email",
     *         "name": "New Email Received",
     *         "description": "Triggered when a new email arrives in Gmail",
     *         "config_schema": {
     *           "from": { "type": "string", "required": false, "description": "Filter by sender email" },
     *           "subject_contains": { "type": "string", "required": false, "description": "Filter by subject keywords" }
     *         }
     *       },
     *       {
     *         "id": "new_calendar_event",
     *         "name": "New Calendar Event",
     *         "description": "Triggered when a new event is created in Google Calendar",
     *         "config_schema": {
     *           "calendar_id": { "type": "string", "required": false, "default": "primary" }
     *         }
     *       }
     *     ],
     *     "actions": [
     *       {
     *         "id": "send_email",
     *         "name": "Send Email",
     *         "description": "Send an email via Gmail",
     *         "config_schema": {
     *           "to": { "type": "string", "required": true, "description": "Recipient email address" },
     *           "subject": { "type": "string", "required": true, "description": "Email subject" },
     *           "body": { "type": "string", "required": true, "description": "Email body content" }
     *         }
     *       },
     *       {
     *         "id": "create_calendar_event",
     *         "name": "Create Calendar Event",
     *         "description": "Create a new event in Google Calendar",
     *         "config_schema": {
     *           "title": { "type": "string", "required": true },
     *           "start_time": { "type": "datetime", "required": true },
     *           "end_time": { "type": "datetime", "required": true },
     *           "description": { "type": "string", "required": false }
     *         }
     *       }
     *     ]
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Service not found"
     * }
     */
    public function show(Request $request, $serviceId)
    {
        $service = Service::find($serviceId);
        
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        
        $serviceDetails = [
            'id' => $service->id,
            'name' => $service->name,
            'description' => $service->description ?? $this->getServiceDescription($service->name),
            'icon' => $service->icon ?? $this->getServiceIcon($service->name),
            'triggers' => $this->getServiceTriggers($service->name),
            'actions' => $this->getServiceActions($service->name),
        ];
        
        return response()->json(['service' => $serviceDetails], 200);
    }

    /**
     * Connect a service via OAuth
     * 
     * Initiates or completes the OAuth connection process for a service.
     * Returns the OAuth URL if not yet connected, or saves the connection
     * if auth_code is provided.
     *
     * @group Services
     * @authenticated
     * 
     * @urlParam serviceId integer required The ID of the service to connect. Example: 1
     * 
     * @bodyParam auth_code string optional The OAuth authorization code (for completing connection).
     * @bodyParam access_token string optional The OAuth access token (for mobile flow).
     * @bodyParam refresh_token string optional The OAuth refresh token.
     * 
     * @response 200 {
     *   "message": "OAuth URL generated",
     *   "oauth_url": "https://accounts.google.com/o/oauth2/auth?..."
     * }
     * 
     * @response 201 {
     *   "message": "Service connected successfully",
     *   "connection": {
     *     "id": 1,
     *     "service_id": 1,
     *     "service_name": "google",
     *     "connected_at": "2025-12-06T10:30:00Z"
     *   }
     * }
     * 
     * @response 404 {
     *   "message": "Service not found"
     * }
     */
    public function connect(Request $request, $serviceId)
    {
        $user = $request->user();
        $service = Service::find($serviceId);
        
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }
        
        // Si le service ne nécessite pas d'auth (ex: Timer)
        if (!$this->requiresAuth($service->name)) {
            // Créer une connexion "virtuelle"
            $userService = UserService::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'service_id' => $service->id,
                ],
                [
                    'access_token' => 'not_required',
                    'name' => $service->name . ' (Active)',
                ]
            );
            
            return response()->json([
                'message' => 'Service connected successfully',
                'connection' => [
                    'id' => $userService->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'connected_at' => $userService->created_at->toIso8601String(),
                ],
            ], 201);
        }
        
        // Si un token est fourni directement (flux mobile)
        if ($request->has('access_token')) {
            $validator = Validator::make($request->all(), [
                'access_token' => 'required|string',
                'refresh_token' => 'nullable|string',
                'expires_at' => 'nullable|integer',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $userService = UserService::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'service_id' => $service->id,
                ],
                [
                    'access_token' => $request->access_token,
                    'refresh_token' => $request->refresh_token,
                    'expires_at' => $request->expires_at ? now()->addSeconds($request->expires_at) : null,
                    'name' => $service->name,
                ]
            );
            
            return response()->json([
                'message' => 'Service connected successfully',
                'connection' => [
                    'id' => $userService->id,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'connected_at' => $userService->created_at->toIso8601String(),
                ],
            ], 201);
        }
        
        // Sinon, retourner l'URL OAuth (flux web)
        $oauthUrl = $this->getOAuthUrl($service->name, $user->id);
        
        return response()->json([
            'message' => 'OAuth URL generated',
            'oauth_url' => $oauthUrl,
        ], 200);
    }

    /**
     * Disconnect a service
     * 
     * Removes the OAuth connection between the user and the service.
     * All AREAs using this service will be deactivated.
     *
     * @group Services
     * @authenticated
     * 
     * @urlParam serviceId integer required The ID of the service to disconnect. Example: 1
     * 
     * @response 200 {
     *   "message": "Service disconnected successfully"
     * }
     * 
     * @response 404 {
     *   "message": "Service connection not found"
     * }
     */
    public function disconnect(Request $request, $serviceId)
    {
        $user = $request->user();
        
        $userService = UserService::where('user_id', $user->id)
            ->where('service_id', $serviceId)
            ->first();
        
        if (!$userService) {
            return response()->json(['message' => 'Service connection not found'], 404);
        }
        
        // Désactiver toutes les AREAs qui utilisent ce service
        \App\Models\Area::where('user_id', $user->id)
            ->where(function ($query) use ($serviceId) {
                $service = Service::find($serviceId);
                if ($service) {
                    $query->where('trigger_service', $service->name)
                          ->orWhere('action_service', $service->name);
                }
            })
            ->update(['is_active' => false]);
        
        $userService->delete();
        
        return response()->json(['message' => 'Service disconnected successfully'], 200);
    }

    /**
     * Get user's connected services
     * 
     * Returns a list of all services that the user has connected to their account.
     *
     * @group Services
     * @authenticated
     * 
     * @response 200 {
     *   "services": [
     *     {
     *       "service_id": 1,
     *       "service_name": "google",
     *       "connected_at": "2025-12-01T08:00:00Z",
     *       "status": "active"
     *     },
     *     {
     *       "service_id": 3,
     *       "service_name": "github",
     *       "connected_at": "2025-11-28T14:30:00Z",
     *       "status": "expired"
     *     }
     *   ]
     * }
     */
    public function getUserServices(Request $request)
    {
        $user = $request->user();
        
        $userServices = UserService::where('user_id', $user->id)
            ->with('service')
            ->get();
        
        $services = $userServices->map(function ($userService) {
            $status = 'active';
            
            // Vérifier si le token a expiré
            if ($userService->expires_at && $userService->expires_at->isPast()) {
                $status = 'expired';
            }
            
            return [
                'service_id' => $userService->service_id,
                'service_name' => $userService->service->name ?? 'Unknown',
                'connected_at' => $userService->created_at->toIso8601String(),
                'status' => $status,
            ];
        });
        
        return response()->json(['services' => $services], 200);
    }

    // ========== HELPER METHODS ==========

    /**
     * Get service description based on service name
     */
    private function getServiceDescription($serviceName)
    {
        $descriptions = [
            'google' => 'Google Workspace services (Gmail, Calendar, Drive)',
            'timer' => 'Schedule actions based on time intervals',
            'github' => 'GitHub repository events and actions',
            'slack' => 'Slack messaging and notifications',
            'discord' => 'Discord messaging and server management',
        ];
        
        return $descriptions[$serviceName] ?? 'Third-party service integration';
    }

    /**
     * Get service icon
     */
    private function getServiceIcon($serviceName)
    {
        $icons = [
            'google' => 'https://www.google.com/favicon.ico',
            'timer' => '⏰',
            'github' => 'https://github.com/favicon.ico',
            'slack' => 'https://slack.com/favicon.ico',
            'discord' => 'https://discord.com/assets/favicon.ico',
        ];
        
        return $icons[$serviceName] ?? '🔌';
    }

    /**
     * Get service color
     */
    private function getServiceColor($serviceName)
    {
        $colors = [
            'google' => '#4285F4',
            'timer' => '#FF6B6B',
            'github' => '#181717',
            'slack' => '#4A154B',
            'discord' => '#5865F2',
        ];
        
        return $colors[$serviceName] ?? '#6C757D';
    }

    /**
     * Get service category
     */
    private function getServiceCategory($serviceName)
    {
        $categories = [
            'google' => 'productivity',
            'timer' => 'utility',
            'github' => 'developer',
            'slack' => 'communication',
            'discord' => 'communication',
        ];
        
        return $categories[$serviceName] ?? 'other';
    }

    /**
     * Check if service requires authentication
     */
    private function requiresAuth($serviceName)
    {
        $noAuthServices = ['timer'];
        return !in_array($serviceName, $noAuthServices);
    }

    /**
     * Get available triggers for a service
     */
    private function getServiceTriggers($serviceName)
    {
        $triggers = [
            'google' => [
                [
                    'id' => 'new_email',
                    'name' => 'New Email Received',
                    'description' => 'Triggered when a new email arrives in Gmail',
                    'config_schema' => [
                        'from' => [
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Filter by sender email address',
                        ],
                        'subject_contains' => [
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Filter by keywords in subject',
                        ],
                    ],
                ],
                [
                    'id' => 'new_calendar_event',
                    'name' => 'New Calendar Event',
                    'description' => 'Triggered when a new event is created in Google Calendar',
                    'config_schema' => [
                        'calendar_id' => [
                            'type' => 'string',
                            'required' => false,
                            'default' => 'primary',
                            'description' => 'Google Calendar ID',
                        ],
                    ],
                ],
            ],
            'timer' => [
                [
                    'id' => 'timer_trigger',
                    'name' => 'Timer Trigger',
                    'description' => 'Triggered at specified intervals (cron-based)',
                    'config_schema' => [
                        'interval' => [
                            'type' => 'string',
                            'required' => true,
                            'enum' => ['every_minute', 'every_5_minutes', 'every_hour', 'every_day'],
                            'description' => 'Time interval for trigger',
                        ],
                    ],
                ],
            ],
            'github' => [
                [
                    'id' => 'new_issue',
                    'name' => 'New Issue Created',
                    'description' => 'Triggered when a new issue is created in a repository',
                    'config_schema' => [
                        'repository' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Repository name (owner/repo)',
                        ],
                    ],
                ],
                [
                    'id' => 'new_pull_request',
                    'name' => 'New Pull Request',
                    'description' => 'Triggered when a new pull request is opened',
                    'config_schema' => [
                        'repository' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Repository name (owner/repo)',
                        ],
                    ],
                ],
            ],
        ];
        
        return $triggers[$serviceName] ?? [];
    }

    /**
     * Get available actions for a service
     */
    private function getServiceActions($serviceName)
    {
        $actions = [
            'google' => [
                [
                    'id' => 'send_email',
                    'name' => 'Send Email',
                    'description' => 'Send an email via Gmail',
                    'config_schema' => [
                        'to' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Recipient email address',
                        ],
                        'subject' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Email subject',
                        ],
                        'body' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Email body content',
                        ],
                    ],
                ],
                [
                    'id' => 'create_calendar_event',
                    'name' => 'Create Calendar Event',
                    'description' => 'Create a new event in Google Calendar',
                    'config_schema' => [
                        'title' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Event title',
                        ],
                        'start_time' => [
                            'type' => 'datetime',
                            'required' => true,
                            'description' => 'Event start time (ISO 8601)',
                        ],
                        'end_time' => [
                            'type' => 'datetime',
                            'required' => true,
                            'description' => 'Event end time (ISO 8601)',
                        ],
                        'description' => [
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Event description',
                        ],
                    ],
                ],
            ],
            'github' => [
                [
                    'id' => 'create_issue',
                    'name' => 'Create Issue',
                    'description' => 'Create a new issue in a GitHub repository',
                    'config_schema' => [
                        'repository' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Repository name (owner/repo)',
                        ],
                        'title' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Issue title',
                        ],
                        'body' => [
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Issue description',
                        ],
                    ],
                ],
            ],
            'slack' => [
                [
                    'id' => 'send_message',
                    'name' => 'Send Message',
                    'description' => 'Send a message to a Slack channel',
                    'config_schema' => [
                        'channel' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Channel name or ID',
                        ],
                        'message' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Message content',
                        ],
                    ],
                ],
            ],
            'discord' => [
                [
                    'id' => 'send_message',
                    'name' => 'Send Message',
                    'description' => 'Send a message to a Discord channel',
                    'config_schema' => [
                        'channel_id' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Discord channel ID',
                        ],
                        'message' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'Message content',
                        ],
                    ],
                ],
            ],
        ];
        
        return $actions[$serviceName] ?? [];
    }

    /**
     * Generate OAuth URL for a service
     */
    private function getOAuthUrl($serviceName, $userId)
    {
        // Cette méthode devrait être implémentée avec la vraie logique OAuth
        // Pour l'instant, on retourne une URL d'exemple
        
        $baseUrls = [
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'github' => 'https://github.com/login/oauth/authorize',
            'slack' => 'https://slack.com/oauth/v2/authorize',
            'discord' => 'https://discord.com/api/oauth2/authorize',
        ];
        
        $url = $baseUrls[$serviceName] ?? '#';
        
        // Ajouter les paramètres OAuth (à adapter selon votre config)
        if ($serviceName === 'google') {
            $params = http_build_query([
                'client_id' => config('services.google.client_id'),
                'redirect_uri' => url('/api/services/google/callback'),
                'response_type' => 'code',
                'scope' => 'email profile https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/calendar.readonly',
                'state' => base64_encode(json_encode(['user_id' => $userId])),
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]);
            
            return $url . '?' . $params;
        }
        
        return $url;
    }

    /**
     * Handle OAuth callback from Google/GitHub/etc after user authorizes
     */
    public function handleServiceCallback($service, Request $request)
    {
        try {
            if (!$request->has('code')) {
                \Log::error('No code in service callback', ['service' => $service]);
                return redirect(env('FRONTEND_URL', 'http://localhost:8081') . '/services?error=no_code');
            }

            // Décoder le state pour récupérer le user_id
            $state = json_decode(base64_decode($request->state), true);
            $userId = $state['user_id'] ?? null;

            if (!$userId) {
                \Log::error('No user_id in state', ['state' => $request->state]);
                return redirect(env('FRONTEND_URL', 'http://localhost:8081') . '/services?error=invalid_state');
            }

            $user = \App\Models\User::find($userId);
            if (!$user) {
                return redirect(env('FRONTEND_URL', 'http://localhost:8081') . '/services?error=user_not_found');
            }

            // Exchange code for access token (Google example)
            if ($service === 'google') {
                $tokenResponse = \Http::post('https://oauth2.googleapis.com/token', [
                    'code' => $request->code,
                    'client_id' => env('GOOGLE_WEB_CLIENT_ID'),
                    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                    'redirect_uri' => url('/api/services/google/callback'),
                    'grant_type' => 'authorization_code',
                ]);

                if (!$tokenResponse->successful()) {
                    \Log::error('Failed to exchange code', ['response' => $tokenResponse->body()]);
                    return redirect(env('FRONTEND_URL', 'http://localhost:8081') . '/services?error=token_exchange_failed');
                }

                $tokenData = $tokenResponse->json();
                $accessToken = $tokenData['access_token'];
                $refreshToken = $tokenData['refresh_token'] ?? null;

                // Sauvegarder les tokens dans la table user
                $user->update([
                    'google_token' => $accessToken,
                    'google_refresh_token' => $refreshToken,
                ]);

                // Créer/mettre à jour l'entrée dans user_services pour que le frontend le voit comme connecté
                $googleService = \App\Models\Service::where('name', 'google')->first();
                if ($googleService) {
                    \App\Models\UserService::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'service_id' => $googleService->id,
                        ],
                        [
                            'access_token' => $accessToken,
                            'refresh_token' => $refreshToken,
                            'name' => 'google',
                        ]
                    );
                }

                \Log::info('Service connected successfully', ['user_id' => $userId, 'service' => $service]);
            }

            // Rediriger vers le frontend avec succès
            return redirect(env('FRONTEND_URL', 'http://localhost:8081') . '/services?status=connected&service=' . $service);

        } catch (\Exception $e) {
            \Log::error('Service callback error', [
                'service' => $service,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect(env('FRONTEND_URL', 'http://localhost:8081') . '/services?error=' . urlencode($e->getMessage()));
        }
    }
}
