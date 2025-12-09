<script setup lang="ts">
import { ref } from 'vue'

const authStore = useAuthStore()
const { stats, fetchDashboardStats } = useDashboard()
const route = useRoute()
const router = useRouter()

// Vérifier si token dans URL (retour OAuth Google)
onMounted(async () => {
  const tokenFromUrl = route.query.token as string
  
  if (tokenFromUrl) {
    console.log('📥 Token OAuth Google reçu')
    authStore.setToken(tokenFromUrl)
    // Nettoyer l'URL
    router.replace('/dashboard')
  }
  
  // Vérifier authentification
  if (!authStore.isAuthenticated) {
    const isAuth = await authStore.checkAuth()
    if (!isAuth) {
      router.push('/login')
      return
    }
  }
  
  // Charger les stats
  await fetchDashboardStats()
})

// Note: J'ai ajouté le champ "icon" correspondant au nom exact pour le CDN
const recentActivity = ref([
  { id: 1, area: "Gmail ➜ Discord", status: "success", time: "2 min ago", icon: "gmail" },
  { id: 2, area: "Weather ➜ Sms", status: "error", time: "15 min ago", icon: "openweathermap" },
  { id: 3, area: "Github ➜ Trello", status: "success", time: "1 hr ago", icon: "github" },
  { id: 4, area: "Daily Timer", status: "success", time: "3 hrs ago", icon: "clockify" },
])

const recentAreas = ref([
  { id: 1, name: "Auto-Reply Client", on: true, trigger: "Gmail", action: "OpenAI" },
  { id: 2, name: "Dev Workflow", on: true, trigger: "GitHub", action: "Slack" },
  { id: 3, name: "Rain Alert", on: false, trigger: "Weather", action: "Email" },
])

const logout = () => {
  authStore.logout()
  navigateTo('/login')
}

// ✨ LA MAGIE EST ICI : On récupère le logo officiel via CDN
const getServiceLogo = (name: string) => {
  const map: Record<string, string> = {
    'Gmail': 'gmail',
    'Google': 'google',
    'GitHub': 'github',
    'Weather': 'openweathermap',
    'OpenAI': 'openai',
    'Slack': 'slack',
    'Discord': 'discord',
    'Spotify': 'spotify',
    'Trello': 'trello',
    'Email': 'gmail', // Fallback visuel
    'Timer': 'clockify'
  }
  const slug = map[name] || 'lightning'
  // On utilise le CDN simpleicons.org
  return `https://cdn.simpleicons.org/${slug}`
}
</script>

<template>
  <div class="flex h-screen w-full bg-[#F3F4F6] font-sans text-slate-800 overflow-hidden">
    
    <!-- SIDEBAR -->
    <aside class="w-64 hidden md:flex flex-col bg-white border-r border-gray-200 z-20">
      <div class="h-16 flex items-center px-6 border-b border-gray-100">
        <div class="flex items-center gap-2">
          <!-- Logo AREA -->
          <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold shadow-sm shadow-blue-200">
            A
          </div>
          <span class="text-xl font-bold text-gray-800 tracking-tight">AREA</span>
        </div>
      </div>

      <nav class="flex-1 px-3 py-6 space-y-1">
        <NuxtLink to="/dashboard" class="flex items-center px-3 py-2.5 rounded-lg bg-blue-50 text-blue-700 font-semibold transition-all group relative">
          <div class="absolute left-0 top-1 bottom-1 w-1 bg-blue-600 rounded-r-full"></div>
          <span class="text-xl mr-3">📊</span> Dashboard
        </NuxtLink>
        <NuxtLink to="/areas" class="flex items-center px-3 py-2.5 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-900 font-medium transition-all group">
          <span class="text-xl mr-3 group-hover:text-blue-500 transition-colors">⚡</span> My AREAs
        </NuxtLink>
        <NuxtLink to="/services" class="flex items-center px-3 py-2.5 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-900 font-medium transition-all group">
          <span class="text-xl mr-3 group-hover:text-blue-500 transition-colors">🔌</span> Services
        </NuxtLink>
        <NuxtLink to="/activity" class="flex items-center px-3 py-2.5 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-900 font-medium transition-all group">
          <span class="text-xl mr-3 group-hover:text-blue-500 transition-colors">📜</span> Activity
        </NuxtLink>
      </nav>

      <div class="p-4 border-t border-gray-100 bg-gray-50/50">
        <div class="flex items-center gap-3 mb-3">
          <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold border border-blue-200">
            {{ authStore.user?.name?.charAt(0).toUpperCase() }}
          </div>
          <div class="overflow-hidden">
            <p class="text-sm font-bold text-gray-800 truncate">{{ authStore.user?.name }}</p>
            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">User</p>
          </div>
        </div>
        <button @click="logout" class="w-full py-2 rounded-lg border border-gray-200 bg-white text-gray-600 hover:text-red-600 hover:border-red-100 hover:bg-red-50 text-xs font-bold transition-all shadow-sm">
          Déconnexion
        </button>
      </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col h-full overflow-hidden relative">
      <header class="h-16 flex items-center justify-between px-8 bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-10">
        <h1 class="text-lg font-bold text-gray-800">Dashboard</h1>
        <NuxtLink to="/areas/create" class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-semibold shadow-md shadow-blue-200 transition-all transform hover:-translate-y-0.5 text-sm">
          <span>+</span> Create Automation
        </NuxtLink>
      </header>

      <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
        <div class="max-w-6xl mx-auto space-y-8 pb-10">
          
          <!-- STATS -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:border-blue-200 transition">
              <div class="flex justify-between items-start mb-2">
                <span class="text-gray-400 text-xs font-bold uppercase tracking-wider">Runs Today</span>
                <span class="w-8 h-8 rounded-lg bg-green-50 text-green-600 flex items-center justify-center text-sm font-bold">✓</span>
              </div>
              <p class="text-3xl font-black text-gray-900">{{ stats.executionsToday }}</p>
            </div>
             <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:border-blue-200 transition">
              <div class="flex justify-between items-start mb-2">
                <span class="text-gray-400 text-xs font-bold uppercase tracking-wider">Active</span>
                <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg">⚡</span>
              </div>
              <p class="text-3xl font-black text-gray-900">{{ stats.activeAreas }}</p>
            </div>
             <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm hover:border-blue-200 transition">
              <div class="flex justify-between items-start mb-2">
                <span class="text-gray-400 text-xs font-bold uppercase tracking-wider">Services</span>
                <span class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center text-lg">🔌</span>
              </div>
              <p class="text-3xl font-black text-gray-900">{{ stats.connectedServices }}</p>
            </div>
            <div class="p-5 rounded-xl bg-gradient-to-br from-blue-600 to-blue-700 text-white shadow-lg shadow-blue-200 flex flex-col justify-between relative overflow-hidden group cursor-pointer hover:shadow-xl transition-all">
              <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
              <div>
              
                <p class="text-blue-100 text-xs font-bold uppercase tracking-wider mb-1">New Workflow</p>
                <p class="text-lg font-bold leading-tight">Create a new<br>automation.</p>
              </div>
              <div class="flex justify-end">
                <NuxtLink to="/areas/create">
                <span class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-blue-600 transition">→</span>
                </NuxtLink>
              </div>
            </div>
          </div>

          <!-- LISTS -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left: Workflows -->
            <div class="lg:col-span-2 space-y-4">
              <div class="flex justify-between items-end px-1">
                <h3 class="font-bold text-lg text-gray-800">Your Workflows</h3>
              </div>

              <div class="bg-white rounded-xl p-1 shadow-sm border border-gray-200">
                <div v-for="area in recentAreas" :key="area.id" class="group flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition border border-transparent mb-1 cursor-pointer">
                  <div class="flex items-center gap-4">
                    <!-- ICONE LOGO REEL -->
                    <div class="w-12 h-12 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center p-2 group-hover:border-blue-200 group-hover:bg-white transition">
                      <img :src="getServiceLogo(area.trigger)" alt="Service" class="w-6 h-6 object-contain" />
                    </div>
                    
                    <div>
                      <h4 class="font-bold text-gray-900 text-sm group-hover:text-blue-700 transition">{{ area.name }}</h4>
                      <div class="flex items-center gap-2 mt-1 text-xs text-gray-500 font-medium">
                        <span class="flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded">
                           <img :src="getServiceLogo(area.trigger)" class="w-3 h-3" /> {{ area.trigger }}
                        </span>
                        <span class="text-gray-300">➜</span>
                        <span class="flex items-center gap-1 bg-gray-100 px-2 py-0.5 rounded">
                           <img :src="getServiceLogo(area.action)" class="w-3 h-3" /> {{ area.action }}
                        </span>
                      </div>
                    </div>
                  </div>
                  
                  <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-3 py-1 rounded-full text-[10px] font-bold border" 
                      :class="area.on ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-500 border-gray-200'">
                      <span class="w-1.5 h-1.5 rounded-full" :class="area.on ? 'bg-green-500' : 'bg-gray-400'"></span>
                      {{ area.on ? 'ACTIVE' : 'PAUSED' }}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right: Activity Feed -->
            <div class="space-y-4">
               <div class="flex justify-between items-end px-1">
                <h3 class="font-bold text-lg text-gray-800">Live Feed</h3>
              </div>

              <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 h-full max-h-[350px] overflow-y-auto custom-scrollbar">
                <div class="space-y-6 relative">
                  <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-gray-100 rounded"></div>

                  <div v-for="log in recentActivity" :key="log.id" class="relative pl-6">
                    <div class="absolute left-0 top-1.5 w-4 h-4 rounded-full border-2 border-white shadow-sm"
                      :class="log.status === 'success' ? 'bg-green-500' : 'bg-red-500'"></div>
                    
                    <div class="flex justify-between items-start">
                      <div class="flex items-center gap-2">
                        <img :src="`https://cdn.simpleicons.org/${log.icon}`" class="w-3 h-3 opacity-60" />
                        <p class="text-xs font-bold text-gray-700">{{ log.area }}</p>
                      </div>
                      <span class="text-[10px] text-gray-400 font-mono">{{ log.time }}</span>
                    </div>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                      {{ log.status === 'success' ? 'Triggered successfully' : 'Failed to execute action' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>