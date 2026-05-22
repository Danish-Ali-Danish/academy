<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50/50 pb-12">
      <!-- Header & Filters -->
      <div class="bg-white border-b border-gray-200 sticky top-0 z-20 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 lg:flex lg:items-center lg:justify-between">
          <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate flex items-center gap-3">
              <div class="p-2 bg-indigo-100 rounded-xl">
                <ChartPieIcon class="h-6 w-6 text-indigo-600" />
              </div>
              Collection Dashboard
            </h1>
            <p class="mt-2 text-sm text-gray-500">Overview of projected vs actual revenue, payment modes, and defaulters.</p>
          </div>
          <div class="mt-4 flex flex-col sm:flex-row sm:mt-0 sm:ml-4 gap-3 bg-gray-50 p-1.5 rounded-xl border border-gray-100 shadow-inner">
            <div class="relative rounded-md shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <CalendarIcon class="h-4 w-4 text-gray-400" />
              </div>
              <input v-model="filters.start_date" type="date" class="pl-10 block w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
            </div>
            <div class="relative rounded-md shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <CalendarIcon class="h-4 w-4 text-gray-400" />
              </div>
              <input v-model="filters.end_date" type="date" class="pl-10 block w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
            </div>
            <div class="relative rounded-md shadow-sm min-w-[150px]">
              <select v-model="filters.branch_id" class="block w-full text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 bg-white shadow-sm">
                <option value="">All Branches</option>
                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.branch_name }}</option>
              </select>
            </div>
            <button @click="fetchData" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors gap-2">
              <ArrowPathIcon class="h-4 w-4" :class="{'animate-spin': loading}" />
              <span>Refresh</span>
            </button>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-8">
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <!-- Projected -->
          <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 hover:shadow-md transition-shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-50 rounded-xl p-3">
                  <ChartBarIcon class="h-6 w-6 text-blue-600" aria-hidden="true" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Projected Collection</dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-bold text-gray-900">Rs. {{ projected.toLocaleString() }}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Actual -->
          <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 hover:shadow-md transition-shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-emerald-50 rounded-xl p-3">
                  <BanknotesIcon class="h-6 w-6 text-emerald-600" aria-hidden="true" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Actual Collection</dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-bold text-gray-900">Rs. {{ actual.toLocaleString() }}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-5 py-3 border-t border-gray-100">
              <div class="text-sm">
                <span class="font-medium" :class="variance >= 0 ? 'text-emerald-600' : 'text-red-600'">
                  {{ variance >= 0 ? '+' : '' }}Rs. {{ variance.toLocaleString() }}
                </span>
                <span class="text-gray-500 ml-2">Variance</span>
              </div>
            </div>
          </div>

          <!-- Deposited -->
          <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 hover:shadow-md transition-shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-50 rounded-xl p-3">
                  <BuildingLibraryIcon class="h-6 w-6 text-purple-600" aria-hidden="true" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Bank Deposited</dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-bold text-gray-900">Rs. {{ reconciliation.deposited.toLocaleString() }}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <!-- Cash in Hand -->
          <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100 hover:shadow-md transition-shadow">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-amber-50 rounded-xl p-3">
                  <WalletIcon class="h-6 w-6 text-amber-600" aria-hidden="true" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">Cash in Hand</dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-bold text-gray-900">Rs. {{ reconciliation.cash_in_hand.toLocaleString() }}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
          
          <!-- Collection Overview Chart -->
          <div class="col-span-1 lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Collection Overview</h3>
            </div>
            <div class="relative h-72 w-full">
              <canvas ref="projChartRef"></canvas>
            </div>
          </div>

          <!-- Payment Modes Donut -->
          <div class="col-span-1 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg leading-6 font-medium text-gray-900">Payment Modes</h3>
            </div>
            <div class="relative flex-1 flex items-center justify-center min-h-[250px]">
              <canvas ref="modesChartRef"></canvas>
              <!-- Center text for donut -->
              <div v-if="Object.keys(modes).length > 0" class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-4">
                <span class="text-sm text-gray-500">Total</span>
                <span class="text-xl font-bold text-gray-900">{{ actual >= 1000 ? (actual/1000).toFixed(1) + 'k' : actual }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Defaulters Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="px-6 py-5 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between bg-gray-50/50 gap-4">
            <div class="flex items-center gap-3">
              <div class="p-2 bg-red-100 rounded-lg">
                <ExclamationTriangleIcon class="h-5 w-5 text-red-600" />
              </div>
              <h3 class="text-lg leading-6 font-medium text-gray-900">Top Defaulters</h3>
            </div>
            <div class="text-sm font-medium text-red-600 bg-red-50 px-3 py-1 rounded-full w-fit">
              Total Arrears: Rs. {{ defaulters.total_arrears.toLocaleString() }}
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-white">
                <tr>
                  <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                  <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Pending Dues</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-100">
                <tr v-if="!defaulters.top_defaulters.length">
                  <td colspan="2" class="px-6 py-8 text-center text-sm text-gray-500">
                    No defaulters found for this period. Great job!
                  </td>
                </tr>
                <tr v-for="(d, index) in defaulters.top_defaulters" :key="d.enrollment_id" class="hover:bg-gray-50 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center text-indigo-700 font-bold shadow-inner">
                          {{ d.student_name ? d.student_name.charAt(0).toUpperCase() : 'U' }}
                        </div>
                      </div>
                      <div class="ml-4">
                        <div class="text-sm font-medium text-gray-900">{{ d.student_name }}</div>
                        <div class="text-xs text-gray-500">ID: #{{ d.enrollment_id }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-red-50 text-red-700 border border-red-100">
                      Rs. {{ Number(d.total_due).toLocaleString() }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import axios from 'axios'
import Chart from 'chart.js/auto'
import { 
  BanknotesIcon, 
  BuildingLibraryIcon, 
  WalletIcon,
  ChartBarIcon,
  ChartPieIcon,
  ExclamationTriangleIcon,
  CalendarIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const branches = ref([])
const filters = ref({ start_date: '', end_date: '', branch_id: '' })
const modes = ref({})
const projected = ref(0)
const actual = ref(0)
const variance = ref(0)
const reconciliation = ref({ deposited: 0, cash_in_hand: 0 })
const defaulters = ref({ total_arrears: 0, top_defaulters: [] })
const loading = ref(false)

let projChart = null
let modesChart = null

const fetchBranches = async () => {
  try {
    const res = await axios.get(route('branches.dropdown'))
    branches.value = res.data
  } catch (e) { branches.value = [] }
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.value.start_date) params.start_date = filters.value.start_date
    if (filters.value.end_date) params.end_date = filters.value.end_date
    if (filters.value.branch_id) params.branch_id = filters.value.branch_id

    const res = await axios.get(route('fee-collection-summaries.dashboard-data'), { params })
    modes.value = res.data.modes || {}
    projected.value = Number(res.data.projected) || 0
    actual.value = Number(res.data.actual) || 0
    variance.value = Number(res.data.variance) || 0
    reconciliation.value = res.data.reconciliation || { deposited: 0, cash_in_hand: 0 }
    defaulters.value = res.data.defaulters || { total_arrears: 0, top_defaulters: [] }

    await nextTick()
    renderProjChart()
    renderModesChart()
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const renderProjChart = () => {
  if (!projChartRef.value) return;
  const ctx = projChartRef.value.getContext('2d')
  if (projChart) projChart.destroy()
  
  // Create gradient
  const gradientProjected = ctx.createLinearGradient(0, 0, 0, 400);
  gradientProjected.addColorStop(0, 'rgba(99, 102, 241, 0.8)'); // indigo-500
  gradientProjected.addColorStop(1, 'rgba(99, 102, 241, 0.2)');

  const gradientActual = ctx.createLinearGradient(0, 0, 0, 400);
  gradientActual.addColorStop(0, 'rgba(16, 185, 129, 0.8)'); // emerald-500
  gradientActual.addColorStop(1, 'rgba(16, 185, 129, 0.2)');

  projChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Projected', 'Actual'],
      datasets: [{ 
        label: 'Amount (Rs)', 
        data: [projected.value, actual.value], 
        backgroundColor: [gradientProjected, gradientActual],
        borderColor: ['rgb(99, 102, 241)', 'rgb(16, 185, 129)'],
        borderWidth: 1,
        borderRadius: 8,
        barPercentage: 0.6
      }]
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: false,
      plugins: { 
        legend: { display: false },
        tooltip: {
          backgroundColor: 'rgba(17, 24, 39, 0.9)',
          padding: 12,
          titleFont: { size: 14, family: "'Inter', sans-serif" },
          bodyFont: { size: 14, family: "'Inter', sans-serif" },
          callbacks: {
            label: function(context) {
              return ' Rs. ' + context.raw.toLocaleString();
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(243, 244, 246, 1)',
            drawBorder: false,
          },
          ticks: {
            font: { family: "'Inter', sans-serif", color: '#6B7280' },
            callback: function(value) { return 'Rs ' + (value >= 1000 ? value/1000 + 'k' : value); }
          }
        },
        x: {
          grid: { display: false, drawBorder: false },
          ticks: { font: { family: "'Inter', sans-serif", size: 13, weight: '500', color: '#374151' } }
        }
      }
    }
  })
}

const renderModesChart = () => {
  if (!modesChartRef.value) return;
  const ctx = modesChartRef.value.getContext('2d')
  if (modesChart) modesChart.destroy()
  
  const rawLabels = Object.keys(modes.value)
  const labels = rawLabels.map(l => l.charAt(0).toUpperCase() + l.slice(1).replace('_', ' '))
  const data = Object.values(modes.value)
  
  const colors = [
    '#4F46E5', // indigo-600
    '#0EA5E9', // sky-500
    '#10B981', // emerald-500
    '#F59E0B', // amber-500
    '#8B5CF6', // violet-500
  ];

  modesChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{ 
        data, 
        backgroundColor: colors.slice(0, Math.max(1, data.length)),
        borderWidth: 0,
        hoverOffset: 4
      }]
    },
    options: { 
      responsive: true,
      maintainAspectRatio: false,
      cutout: '75%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            usePointStyle: true,
            padding: 20,
            font: { family: "'Inter', sans-serif", size: 12, color: '#4B5563' }
          }
        },
        tooltip: {
          backgroundColor: 'rgba(17, 24, 39, 0.9)',
          padding: 12,
          callbacks: {
            label: function(context) {
              const value = context.raw;
              const total = context.dataset.data.reduce((a,b) => a+b, 0) || 1;
              const percentage = Math.round((value / total) * 100);
              return ` ${context.label}: Rs. ${value.toLocaleString()} (${percentage}%)`;
            }
          }
        }
      }
    }
  })
}

const projChartRef = ref(null)
const modesChartRef = ref(null)

onMounted(() => {
  // defaults
  const today = new Date();
  const start = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().slice(0,10)
  const end = new Date(today.getFullYear(), today.getMonth()+1, 0).toISOString().slice(0,10)
  filters.value.start_date = start
  filters.value.end_date = end
  fetchBranches()
  fetchData()
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.font-sans {
  font-family: 'Inter', sans-serif;
}
</style>
