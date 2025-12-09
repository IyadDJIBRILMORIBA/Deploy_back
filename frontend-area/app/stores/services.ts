import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useAuthStore } from './auth'

export interface Service {
    id: number
    name: string
    description: string
    icon: string
    color: string
    category: string
    is_connected: boolean
    requires_auth: boolean
}

export interface ServiceTrigger {
    id: string
    name: string
    description: string
    config_schema: Record<string, any>
}

export interface ServiceAction {
    id: string
    name: string
    description: string
    config_schema: Record<string, any>
}

export interface ServiceDetails extends Service {
    triggers: ServiceTrigger[]
    actions: ServiceAction[]
}

export interface UserService {
    id: number
    service_id: number
    service_name: string
    connected_at: string
}

export const useServicesStore = defineStore('services', () => {
    const config = useRuntimeConfig()
    const apiBaseUrl = config.public.apiBase
    const authStore = useAuthStore()

    const services = ref<Service[]>([])
    const userServices = ref<UserService[]>([])
    const isLoading = ref(false)
    const error = ref<any>(null)

    // -----------------------------
    // Récupérer tous les services disponibles
    // -----------------------------
    async function fetchServices() {
        if (!authStore.token) {
            throw new Error('Non authentifié')
        }

        isLoading.value = true
        error.value = null

        try {
            const response = await $fetch<{ services: Service[] }>(
                `${apiBaseUrl}/api/services`,
                {
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                    },
                }
            )
            services.value = response.services
            return response.services
        } catch (e) {
            error.value = e
            console.error('Erreur lors de la récupération des services:', e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    // -----------------------------
    // Récupérer les détails d'un service
    // -----------------------------
    async function fetchServiceById(serviceId: number) {
        if (!authStore.token) {
            throw new Error('Non authentifié')
        }

        isLoading.value = true
        error.value = null

        try {
            const response = await $fetch<{ service: ServiceDetails }>(
                `${apiBaseUrl}/api/services/${serviceId}`,
                {
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                    },
                }
            )
            return response.service
        } catch (e) {
            error.value = e
            console.error('Erreur lors de la récupération des détails du service:', e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    // -----------------------------
    // Connecter un service via OAuth
    // -----------------------------
    async function connectService(serviceId: number, params?: {
        auth_code?: string
        access_token?: string
        refresh_token?: string
    }) {
        if (!authStore.token) {
            throw new Error('Non authentifié')
        }

        isLoading.value = true
        error.value = null

        try {
            const response = await $fetch<{
                message: string
                oauth_url?: string
                connection?: UserService
            }>(
                `${apiBaseUrl}/api/services/${serviceId}/connect`,
                {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: params || {},
                }
            )

            // Si une URL OAuth est retournée, rediriger l'utilisateur
            if (response.oauth_url) {
                window.location.href = response.oauth_url
            }

            // Si la connexion est établie, rafraîchir la liste
            if (response.connection) {
                await fetchUserServices()
            }

            return response
        } catch (e) {
            error.value = e
            console.error('Erreur lors de la connexion au service:', e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    // -----------------------------
    // Déconnecter un service
    // -----------------------------
    async function disconnectService(serviceId: number) {
        if (!authStore.token) {
            throw new Error('Non authentifié')
        }

        isLoading.value = true
        error.value = null

        try {
            const response = await $fetch<{ message: string }>(
                `${apiBaseUrl}/api/services/${serviceId}/disconnect`,
                {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                    },
                }
            )

            // Rafraîchir les listes
            await Promise.all([
                fetchUserServices(),
                fetchServices()
            ])

            return response
        } catch (e) {
            error.value = e
            console.error('Erreur lors de la déconnexion du service:', e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    // -----------------------------
    // Récupérer les services connectés de l'utilisateur
    // -----------------------------
    async function fetchUserServices() {
        if (!authStore.token) {
            throw new Error('Non authentifié')
        }

        isLoading.value = true
        error.value = null

        try {
            const response = await $fetch<{ services: UserService[] }>(
                `${apiBaseUrl}/api/user/services`,
                {
                    headers: {
                        'Authorization': `Bearer ${authStore.token}`,
                        'Accept': 'application/json',
                    },
                }
            )
            userServices.value = response.services
            return response.services
        } catch (e) {
            error.value = e
            console.error('Erreur lors de la récupération des services utilisateur:', e)
            throw e
        } finally {
            isLoading.value = false
        }
    }

    return {
        services,
        userServices,
        isLoading,
        error,
        fetchServices,
        fetchServiceById,
        connectService,
        disconnectService,
        fetchUserServices,
    }
})
