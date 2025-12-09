<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useAreasStore } from '~/stores/areas'
import { useServicesStore } from '~/stores/services'

const authStore = useAuthStore()
const areasStore = useAreasStore()
const servicesStore = useServicesStore()
const router = useRouter()

onMounted(async () => {
  await servicesStore.fetchServices()
})

const step = ref(1)
const selectedTrigger = ref<any>(null)
const selectedAction = ref<any>(null)
const triggerConfig = ref<Record<string, any>>({})
const actionConfig = ref<Record<string, any>>({})
const areaName = ref('')

// Services avec fields dynamiques
const services = ref([
  {
    id: 'timer',
    name: 'Timer',
    icon: '⏱️',
    triggers: [
      {
        id: 'every_minute',
        name: 'Every Minute',
        description: 'Triggers every minute',
        fields: []
      },
      {
        id: 'every_hour',
        name: 'Every Hour',
        description: 'Triggers every hour',
        fields: []
      },
      {
        id: 'every_day',
        name: 'Every Day',
        description: 'Triggers every day at a specific time',
        fields: [
          { name: 'time', label: 'Time (HH:MM)', type: 'time', required: true, placeholder: '09:00' }
        ]
      }
    ],
    actions: []
  },
  {
    id: 'gmail',
    name: 'Gmail',
    icon: '📧',
    triggers: [
      {
        id: 'new_email',
        name: 'New Email Received',
        description: 'Triggers when you receive a new email',
        fields: [
          { name: 'from', label: 'From (email)', type: 'text', required: false, placeholder: 'sender@example.com' },
          { name: 'subject_contains', label: 'Subject contains', type: 'text', required: false, placeholder: 'invoice' }
        ]
      },
      {
        id: 'email_labeled',
        name: 'Email Labeled',
        description: 'Triggers when an email is labeled',
        fields: [
          { name: 'label', label: 'Label name', type: 'text', required: true, placeholder: 'Important' }
        ]
      }
    ],
    actions: [
      {
        id: 'send_email',
        name: 'Send Email',
        description: 'Send an email to a recipient',
        fields: [
          { name: 'to', label: 'To', type: 'email', required: true, placeholder: 'recipient@example.com' },
          { name: 'subject', label: 'Subject', type: 'text', required: true, placeholder: 'Email subject' },
          { name: 'body', label: 'Message', type: 'textarea', required: true, placeholder: 'Email body' }
        ]
      }
    ]
  },
  {
    id: 'github',
    name: 'GitHub',
    icon: '⚫',
    triggers: [
      {
        id: 'new_issue',
        name: 'New Issue',
        description: 'Triggers when a new issue is created',
        fields: [
          { name: 'repo', label: 'Repository', type: 'text', required: true, placeholder: 'owner/repo' }
        ]
      },
      {
        id: 'new_pr',
        name: 'New Pull Request',
        description: 'Triggers when a new PR is opened',
        fields: [
          { name: 'repo', label: 'Repository', type: 'text', required: true, placeholder: 'owner/repo' }
        ]
      }
    ],
    actions: [
      {
        id: 'create_issue',
        name: 'Create Issue',
        description: 'Creates a new issue',
        fields: [
          { name: 'repo', label: 'Repository', type: 'text', required: true, placeholder: 'owner/repo' },
          { name: 'title', label: 'Title', type: 'text', required: true, placeholder: 'Issue title' },
          { name: 'body', label: 'Description', type: 'textarea', required: false, placeholder: 'Issue description' }
        ]
      }
    ]
  },
  {
    id: 'discord',
    name: 'Discord',
    icon: '🟣',
    triggers: [],
    actions: [
      {
        id: 'send_message',
        name: 'Send Message',
        description: 'Send a message to a Discord channel',
        fields: [
          { name: 'webhook_url', label: 'Webhook URL', type: 'url', required: true, placeholder: 'https://discord.com/api/webhooks/...' },
          { name: 'message', label: 'Message', type: 'textarea', required: true, placeholder: 'Message content' }
        ]
      }
    ]
  },
  {
    id: 'slack',
    name: 'Slack',
    icon: '💬',
    triggers: [],
    actions: [
      {
        id: 'send_message',
        name: 'Send Message',
        description: 'Send a message to a Slack channel',
        fields: [
          { name: 'channel', label: 'Channel', type: 'text', required: true, placeholder: '#general' },
          { name: 'message', label: 'Message', type: 'textarea', required: true, placeholder: 'Message content' }
        ]
      }
    ]
  }
])

const handleSelectTrigger = (service: any, trigger: any) => {
  selectedTrigger.value = { service, trigger }
  triggerConfig.value = {}
  step.value = 2
}

const handleSelectAction = (service: any, action: any) => {
  selectedAction.value = { service, action }
  actionConfig.value = {}
  step.value = 4
}

const handleSubmit = async () => {
  // 1. On vérifie les variables REELLES utilisées dans le template
  if (!areaName.value || !selectedTrigger.value || !selectedAction.value) {
    alert('Veuillez remplir tous les champs obligatoires')
    return
  }

  try {
    // 2. On construit l'objet à envoyer à partir des sélections
    await areasStore.createArea({
      name: areaName.value, // Le nom saisi
      
      // On récupère les IDs depuis l'objet sélectionné
      trigger_service: selectedTrigger.value.service.id, 
      trigger_action: selectedTrigger.value.trigger.id,
      trigger_params: triggerConfig.value, // Les configs saisies
      
      action_service: selectedAction.value.service.id,
      action_reaction: selectedAction.value.action.id,
      action_params: actionConfig.value
    })
    
    router.push('/areas')
  } catch (error) {
    console.error('Erreur création AREA:', error)
    alert('Erreur lors de la création de l\'AREA')
  }
}

const logout = () => { authStore.logout() ; navigateTo('/login') }

const progressPercentage = computed(() => (step.value / 4) * 100)
</script>

<template>
  <div class="flex h-screen w-full bg-[#F3F4F6] font-sans text-slate-800 overflow-hidden">
    
    <!-- SIDEBAR -->
    <aside class="w-64 hidden md:flex flex-col bg-white border-r border-gray-200 z-20">
      <div class="h-16 flex items-center px-6 border-b border-gray-100">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold shadow-sm shadow-blue-200">A</div>
          <span class="text-xl font-bold text-gray-800 tracking-tight">AREA</span>
        </div>
      </div>
      <nav class="flex-1 px-3 py-6 space-y-1">
        <NuxtLink to="/dashboard" class="flex items-center px-3 py-2.5 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-900 font-medium transition-all group">
          <span class="text-xl mr-3 group-hover:text-blue-500 transition-colors">📊</span> Dashboard
        </NuxtLink>
        <NuxtLink to="/areas" class="flex items-center px-3 py-2.5 rounded-lg bg-blue-50 text-blue-700 font-semibold transition-all group relative">
          <div class="absolute left-0 top-1 bottom-1 w-1 bg-blue-600 rounded-r-full"></div>
          <span class="text-xl mr-3">⚡</span> My AREAs
        </NuxtLink>
        <NuxtLink to="/services" class="flex items-center px-3 py-2.5 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-900 font-medium transition-all group">
          <span class="text-xl mr-3 group-hover:text-blue-500 transition-colors">🔌</span> Services
        </NuxtLink>
        <NuxtLink to="/activity" class="flex items-center px-3 py-2.5 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-900 font-medium transition-all group">
          <span class="text-xl mr-3 group-hover:text-blue-500 transition-colors">📜</span> Activity
        </NuxtLink>
      </nav>
      <div class="p-4 border-t border-gray-100 bg-gray-50/50">
        <button @click="logout" class="w-full py-2 rounded-lg border border-gray-200 bg-white text-gray-600 hover:text-red-600 hover:bg-red-50 text-xs font-bold transition-all">Déconnexion</button>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 flex flex-col h-full overflow-hidden">
      
      <!-- Header avec progression -->
      <header class="bg-white border-b border-gray-200 p-6">
        <div class="max-w-4xl mx-auto">
          <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Create New Automation</h1>
            <div class="flex items-center gap-2">
              <div v-for="s in [1,2,3,4]" :key="s" 
                class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm transition"
                :class="s === step ? 'bg-blue-600 text-white' : s < step ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-400'">
                {{ s < step ? '✓' : s }}
              </div>
            </div>
          </div>
          
          <div class="flex items-center gap-3 text-sm">
            <span :class="step >= 1 ? 'text-blue-600 font-semibold' : 'text-gray-400'">Choose Trigger</span>
            <span class="text-gray-300">→</span>
            <span :class="step >= 2 ? 'text-blue-600 font-semibold' : 'text-gray-400'">Configure</span>
            <span class="text-gray-300">→</span>
            <span :class="step >= 3 ? 'text-blue-600 font-semibold' : 'text-gray-400'">Choose Action</span>
            <span class="text-gray-300">→</span>
            <span :class="step >= 4 ? 'text-blue-600 font-semibold' : 'text-gray-400'">Review</span>
          </div>
        </div>
      </header>

      <div class="flex-1 overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto">
          
          <!-- STEP 1: Choose Trigger -->
          <div v-if="step === 1" class="space-y-6">
            <h2 class="text-xl font-bold text-gray-900">When this happens...</h2>
            
            <div v-for="service in services.filter(s => s.triggers.length > 0)" :key="service.id" class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
              <div class="flex items-center gap-3 mb-4">
                <span class="text-3xl">{{ service.icon }}</span>
                <h3 class="text-lg font-bold text-gray-900">{{ service.name }}</h3>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <button v-for="trigger in service.triggers" :key="trigger.id"
                  @click="handleSelectTrigger(service, trigger)"
                  class="text-left p-4 rounded-lg border border-gray-200 hover:border-blue-500 hover:bg-blue-50 transition group">
                  <h4 class="font-semibold text-gray-900 group-hover:text-blue-700 mb-1">{{ trigger.name }}</h4>
                  <p class="text-sm text-gray-500">{{ trigger.description }}</p>
                </button>
              </div>
            </div>
          </div>

          <!-- STEP 2: Configure Trigger -->
          <div v-if="step === 2 && selectedTrigger" class="bg-white rounded-xl shadow-sm p-8 border border-gray-200">
            <div class="flex items-center gap-3 mb-6">
              <span class="text-3xl">{{ selectedTrigger.service.icon }}</span>
              <div>
                <h2 class="text-xl font-bold text-gray-900">{{ selectedTrigger.trigger.name }}</h2>
                <p class="text-sm text-gray-500">{{ selectedTrigger.trigger.description }}</p>
              </div>
            </div>

            <div v-if="selectedTrigger.trigger.fields.length > 0">
              <div v-for="field in selectedTrigger.trigger.fields" :key="field.name" class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-500 ml-1">*</span>
                </label>
                
                <textarea v-if="field.type === 'textarea'"
                  v-model="triggerConfig[field.name]"
                  :placeholder="field.placeholder"
                  :required="field.required"
                  rows="4"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none" />
                
                <input v-else
                  v-model="triggerConfig[field.name]"
                  :type="field.type"
                  :placeholder="field.placeholder"
                  :required="field.required"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
              </div>
            </div>
            <p v-else class="text-gray-500 text-center py-8">No configuration needed for this trigger.</p>

            <div class="flex gap-3 mt-6">
              <button @click="step = 1" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50">← Back</button>
              <button @click="step = 3" class="flex-1 px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700">Next: Choose Action →</button>
            </div>
          </div>

          <!-- STEP 3: Choose Action -->
          <div v-if="step === 3" class="space-y-6">
            <h2 class="text-xl font-bold text-gray-900">Then do this...</h2>
            
            <div v-for="service in services.filter(s => s.actions.length > 0)" :key="service.id" class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
              <div class="flex items-center gap-3 mb-4">
                <span class="text-3xl">{{ service.icon }}</span>
                <h3 class="text-lg font-bold text-gray-900">{{ service.name }}</h3>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <button v-for="action in service.actions" :key="action.id"
                  @click="handleSelectAction(service, action)"
                  class="text-left p-4 rounded-lg border border-gray-200 hover:border-blue-500 hover:bg-blue-50 transition group">
                  <h4 class="font-semibold text-gray-900 group-hover:text-blue-700 mb-1">{{ action.name }}</h4>
                  <p class="text-sm text-gray-500">{{ action.description }}</p>
                </button>
              </div>
            </div>
            
            <button @click="step = 2" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50">← Back</button>
          </div>

          <!-- STEP 4: Configure Action & Review -->
          <div v-if="step === 4 && selectedAction" class="space-y-6">
            
            <!-- Action Config -->
            <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-200">
              <div class="flex items-center gap-3 mb-6">
                <span class="text-3xl">{{ selectedAction.service.icon }}</span>
                <div>
                  <h2 class="text-xl font-bold text-gray-900">{{ selectedAction.action.name }}</h2>
                  <p class="text-sm text-gray-500">{{ selectedAction.action.description }}</p>
                </div>
              </div>

              <div v-for="field in selectedAction.action.fields" :key="field.name" class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                  {{ field.label }}
                  <span v-if="field.required" class="text-red-500 ml-1">*</span>
                </label>
                
                <textarea v-if="field.type === 'textarea'"
                  v-model="actionConfig[field.name]"
                  :placeholder="field.placeholder"
                  :required="field.required"
                  rows="4"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none" />
                
                <input v-else
                  v-model="actionConfig[field.name]"
                  :type="field.type"
                  :placeholder="field.placeholder"
                  :required="field.required"
                  class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" />
              </div>
            </div>

            <!-- Review -->
            <div class="bg-white rounded-xl shadow-sm p-8 border border-gray-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Name your automation</h3>
              <input v-model="areaName" type="text"
                :placeholder="`${selectedTrigger.service.name} → ${selectedAction.service.name}`"
                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none mb-6" />

              <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h4 class="font-semibold text-blue-900 mb-3">Summary</h4>
                <div class="flex items-center gap-4 text-sm">
                  <div class="flex items-center gap-2">
                    <span class="text-2xl">{{ selectedTrigger.service.icon }}</span>
                    <span class="font-medium text-gray-700">{{ selectedTrigger.trigger.name }}</span>
                  </div>
                  <span class="text-blue-600 text-xl">→</span>
                  <div class="flex items-center gap-2">
                    <span class="text-2xl">{{ selectedAction.service.icon }}</span>
                    <span class="font-medium text-gray-700">{{ selectedAction.action.name }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex gap-3">
              <button @click="step = 3" class="px-6 py-2.5 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50">← Back</button>
              <button @click="handleSubmit" class="flex-1 px-6 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 shadow-lg">✓ Create Automation</button>
            </div>
          </div>

        </div>
      </div>
    </main>
  </div>
</template>