<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class AboutController extends Controller
{
    /**
     * Get information about the server and available services
     * 
     * Returns metadata about the AREA server including available services, actions, and reactions.
     *
     * @group About
     * @unauthenticated
     * 
     * @response 200 {
     *   "client": {
     *     "host": "192.168.1.100"
     *   },
     *   "server": {
     *     "current_time": 1733400000,
     *     "services": [
     *       {
     *         "name": "google",
     *         "actions": [
     *           {
     *             "name": "new_email",
     *             "description": "Triggered when a new email is received"
     *           }
     *         ],
     *         "reactions": [
     *           {
     *             "name": "send_email",
     *             "description": "Send an email via Gmail"
     *           }
     *         ]
     *       },
     *       {
     *         "name": "timer",
     *         "actions": [
     *           {
     *             "name": "timer_trigger",
     *             "description": "Triggered at specified intervals"
     *           }
     *         ],
     *         "reactions": []
     *       }
     *     ]
     *   }
     * }
     */
    public function getAbout(Request $request)
    {
        // Récupérer tous les services actifs depuis la base de données
        $services = Service::where('is_active', true)->get();
        
        // Construire le tableau des services avec leurs triggers et actions
        $servicesData = [];
        
        foreach ($services as $service) {
            $servicesData[] = [
                'name' => $service->name,
                'actions' => $this->getServiceTriggers($service->name),
                'reactions' => $this->getServiceActions($service->name),
            ];
        }
        
        return response()->json([
            'client' => [
                'host' => $request->ip()
            ],
            'server' => [
                'current_time' => time(),
                'services' => $servicesData
            ]
        ], 200);
    }

    /**
     * Get available triggers (actions) for a service
     */
    private function getServiceTriggers($serviceName)
    {
        $triggers = [
            'google' => [
                [
                    'name' => 'new_email',
                    'description' => 'Triggered when a new email is received'
                ],
                [
                    'name' => 'new_calendar_event',
                    'description' => 'Triggered when a new calendar event is created'
                ]
            ],
            'timer' => [
                [
                    'name' => 'timer_trigger',
                    'description' => 'Triggered at specified intervals (cron-based)'
                ]
            ],
            'github' => [
                [
                    'name' => 'new_issue',
                    'description' => 'Triggered when a new issue is created'
                ],
                [
                    'name' => 'new_pull_request',
                    'description' => 'Triggered when a new PR is opened'
                ]
            ],
            'slack' => [],
            'discord' => [],
        ];
        
        return $triggers[$serviceName] ?? [];
    }

    /**
     * Get available actions (reactions) for a service
     */
    private function getServiceActions($serviceName)
    {
        $actions = [
            'google' => [
                [
                    'name' => 'send_email',
                    'description' => 'Send an email via Gmail'
                ],
                [
                    'name' => 'create_calendar_event',
                    'description' => 'Create a new Google Calendar event'
                ]
            ],
            'github' => [
                [
                    'name' => 'create_issue',
                    'description' => 'Create a new GitHub issue'
                ]
            ],
            'slack' => [
                [
                    'name' => 'send_message',
                    'description' => 'Send a message to a Slack channel'
                ]
            ],
            'discord' => [
                [
                    'name' => 'send_message',
                    'description' => 'Send a message to a Discord channel'
                ]
            ],
            'timer' => [],
        ];
        
        return $actions[$serviceName] ?? [];
    }
}
