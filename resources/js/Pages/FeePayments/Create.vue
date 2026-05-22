<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Collect Fee Payment</h1>
            <p class="mt-1 text-sm text-gray-500">Search student → Select vouchers → Record payment</p>
          </div>
          <Button @click="$inertia.visit(route('fee-payments.index'))" variant="secondary" class="w-full sm:w-auto text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back
          </Button>
        </div>

        <div class="max-w-3xl space-y-4">

          <!-- STEP 1: Student Search -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-4">
              <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">1</span>
              <h2 class="font-semibold text-gray-900">Search Student</h2>
            </div>

            <div class="relative" ref="searchContainer">
              <div class="relative">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                  v-model="searchQuery"
                  @input="onSearchInput"
                  @focus="showDropdown = searchResults.length > 0"
                  type="text"
                  placeholder="Search by name, admission no, or roll no..."
                  class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                />
                <div v-if="searchLoading" class="absolute right-3 top-2.5">
                  <svg class="animate-spin w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                </div>
              </div>

              <!-- Search Results Dropdown -->
              <div v-if="showDropdown && searchResults.length > 0"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden">
                <button
                  v-for="s in searchResults" :key="s.id"
                  @click="selectStudent(s)"
                  type="button"
                  class="w-full flex items-center gap-3 px-4 py-3 hover:bg-indigo-50 transition-colors text-left border-b border-gray-100 last:border-0">
                  <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-indigo-700 font-semibold text-sm">{{ s.student_name?.charAt(0) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm">{{ s.student_name }}</p>
                    <p class="text-xs text-gray-500">{{ s.class_name }} · {{ s.branch_name }} · Adm: {{ s.admission_no }}</p>
                  </div>
                  <span class="text-xs text-gray-400 flex-shrink-0">{{ s.year_name }}</span>
                </button>
              </div>
              <div v-if="showDropdown && searchQuery.length >= 2 && !searchLoading && searchResults.length === 0"
                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow p-4 text-center text-sm text-gray-500">
                Koi student nahi mila
              </div>
            </div>

            <!-- Selected Student Card -->
            <div v-if="selectedStudent" class="mt-3 flex items-center gap-3 p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
              <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center flex-shrink-0">
                <span class="text-white font-bold">{{ selectedStudent.student_name?.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-indigo-900">{{ selectedStudent.student_name }}</p>
                <p class="text-xs text-indigo-600">{{ selectedStudent.class_name }} · {{ selectedStudent.branch_name }} · {{ selectedStudent.year_name }}</p>
              </div>
              <button @click="clearStudent" type="button" class="text-indigo-400 hover:text-indigo-600 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>

          <!-- Advance Payment Toggle -->
          <div v-if="selectedStudent" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <div>
                  <p class="font-semibold text-gray-900 text-sm">Advance Payment</p>
                  <p class="text-xs text-gray-500">Record advance without selecting a voucher</p>
                </div>
              </div>
              <button @click="toggleAdvanceMode" type="button" :class="[
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                isAdvance ? 'bg-indigo-600' : 'bg-gray-200'
              ]">
                <span :class="[
                  'inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow',
                  isAdvance ? 'translate-x-6' : 'translate-x-1'
                ]"/>
              </button>
            </div>

            <!-- Advance Amount Input -->
            <div v-if="isAdvance" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <label class="block text-sm font-medium text-blue-800 mb-2">Advance Amount <span class="text-red-500">*</span></label>
              <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-gray-600">Rs.</span>
                <input
                  v-model.number="advanceAmount"
                  type="number" step="1" min="1"
                  placeholder="Enter advance amount"
                  class="w-48 px-3 py-2 text-sm border border-blue-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500 font-mono bg-white"
                />
              </div>
              <p class="text-xs text-blue-600 mt-2">
                💡 This advance can later be adjusted against pending vouchers from <strong>Advance Adjustments</strong>.
              </p>
            </div>
          </div>

          <!-- STEP 2: Voucher Selection -->
          <div v-if="selectedStudent && !isAdvance" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-4">
              <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">2</span>
              <h2 class="font-semibold text-gray-900">Select Pending Vouchers</h2>
              <span v-if="vouchersLoading" class="ml-2">
                <svg class="animate-spin w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
              </span>
            </div>

            <!-- No vouchers -->
            <div v-if="!vouchersLoading && pendingVouchers.length === 0"
              class="text-center py-8 bg-green-50 rounded-lg border border-green-200">
              <svg class="w-10 h-10 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <p class="text-sm font-medium text-green-700">No pending vouchers</p>
              <p class="text-xs text-green-600 mt-1">All fees are cleared for this student</p>
            </div>

            <!-- Select All Bar + Voucher Cards -->
            <div v-else class="space-y-2">

              <!-- Select All Row -->
              <div class="flex items-center justify-between px-1 pb-2 border-b border-gray-100">
                <button @click="toggleSelectAll" type="button" class="flex items-center gap-2 text-sm font-medium text-indigo-700 hover:text-indigo-900">
                  <div :class="[
                    'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                    allSelected ? 'bg-indigo-600 border-indigo-600' : partialSelected ? 'bg-indigo-100 border-indigo-400' : 'border-gray-300 bg-white'
                  ]">
                    <svg v-if="allSelected" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    <div v-else-if="partialSelected" class="w-2 h-0.5 bg-indigo-600 rounded"></div>
                  </div>
                  Select All ({{ pendingVouchers.length }})
                </button>
                <div v-if="selectedVouchers.length > 0" class="text-sm text-gray-600">
                  <span class="font-semibold text-indigo-600">{{ selectedVouchers.length }} selected</span>
                  <span class="mx-1 text-gray-300">·</span>
                  Total: <span class="font-bold text-gray-900">Rs. {{ totalSelected.toLocaleString() }}</span>
                </div>
              </div>

              <!-- Voucher Cards -->
              <div
                v-for="v in pendingVouchers" :key="v.id"
                @click="toggleVoucher(v)"
                :class="[
                  'w-full p-4 rounded-lg border-2 transition-all cursor-pointer select-none',
                  isVoucherSelected(v.id)
                    ? 'border-indigo-500 bg-indigo-50'
                    : v.is_overdue
                      ? 'border-red-200 bg-red-50 hover:border-red-400'
                      : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
                ]">
                <div class="flex items-start gap-3">

                  <!-- Checkbox -->
                  <div class="mt-0.5 flex-shrink-0">
                    <div :class="[
                      'w-5 h-5 rounded border-2 flex items-center justify-center transition-colors',
                      isVoucherSelected(v.id) ? 'bg-indigo-600 border-indigo-600' : 'border-gray-300 bg-white'
                    ]">
                      <svg v-if="isVoucherSelected(v.id)" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                      </svg>
                    </div>
                  </div>

                  <!-- Voucher Info -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                      <span class="font-semibold text-gray-900 text-sm">{{ v.fee_type }}</span>
                      <span :class="v.status === 'partial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                        class="px-2 py-0.5 text-xs font-medium rounded-full">
                        {{ v.status === 'partial' ? 'Partial' : 'Pending' }}
                      </span>
                      <span v-if="v.is_overdue" class="px-2 py-0.5 text-xs font-medium rounded-full bg-red-600 text-white">Overdue</span>
                    </div>
                    <p class="text-xs text-gray-500">{{ v.voucher_no }} · Due: {{ v.due_date_display }}</p>

                    <!-- Amount input when selected -->
                    <div v-if="isVoucherSelected(v.id)" class="mt-2" @click.stop>
                      <label class="text-xs font-medium text-indigo-700">Amount to pay:</label>
                      <div class="flex items-center gap-2 mt-1">
                        <span class="text-sm text-gray-500 font-medium">Rs.</span>
                        <input
                          :value="getSelectedVoucher(v.id)?.customAmount"
                          @input="updateAmount(v.id, $event.target.value)"
                          type="number" step="1" min="1"
                          :max="v.remaining_amount"
                          class="w-32 px-2 py-1 text-sm border border-indigo-300 rounded-lg focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                        />
                        <span class="text-xs text-gray-400">max Rs. {{ v.remaining_amount.toLocaleString() }}</span>
                      </div>
                    </div>

                    <!-- Partial progress bar (when not selected) -->
                    <div v-if="!isVoucherSelected(v.id) && v.status === 'partial'" class="mt-2">
                      <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Paid: Rs. {{ v.paid_amount.toLocaleString() }}</span>
                        <span>Remaining: Rs. {{ v.remaining_amount.toLocaleString() }}</span>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-yellow-500 h-1.5 rounded-full" :style="{ width: ((v.paid_amount / v.net_amount) * 100) + '%' }"></div>
                      </div>
                    </div>
                  </div>

                  <!-- Remaining amount (when not selected) -->
                  <div v-if="!isVoucherSelected(v.id)" class="text-right flex-shrink-0">
                    <p class="text-lg font-bold" :class="v.is_overdue ? 'text-red-600' : 'text-gray-900'">
                      Rs. {{ v.remaining_amount.toLocaleString() }}
                    </p>
                    <p class="text-xs text-gray-400">remaining</p>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- STEP 3: Payment Details -->
          <div v-if="selectedVouchers.length > 0 || (isAdvance && advanceAmount > 0)" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-2 mb-5">
              <span class="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center">3</span>
              <h2 class="font-semibold text-gray-900">Payment Details</h2>
            </div>

            <!-- Error Alert -->
            <div v-if="flashError || Object.keys(submitErrors).length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
              <div class="flex items-start gap-2">
                <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                  <p v-if="flashError" class="text-sm font-medium text-red-700">{{ flashError }}</p>
                  <ul v-if="Object.keys(submitErrors).length > 0" class="text-sm text-red-700 space-y-1">
                    <li v-for="(msg, field) in submitErrors" :key="field">{{ Array.isArray(msg) ? msg[0] : msg }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">

              <!-- Date -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date <span class="text-red-500">*</span></label>
                  <input
                    v-model="payment_date"
                    type="date"
                    :class="{ 'border-red-500': submitErrors.payment_date }"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                  />
                </div>
              </div>

              <!-- Payment Method -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                  <button
                    v-for="m in paymentMethods" :key="m.value"
                    @click="payment_method = m.value"
                    type="button"
                    :class="[
                      'flex flex-col items-center justify-center p-3 rounded-lg border-2 transition-all text-center',
                      payment_method === m.value
                        ? `border-${m.color}-500 bg-${m.color}-50`
                        : 'border-gray-200 hover:border-gray-300 bg-white'
                    ]">
                    <span class="text-lg mb-1">{{ m.icon }}</span>
                    <span class="text-xs font-medium" :class="payment_method === m.value ? `text-${m.color}-700` : 'text-gray-600'">
                      {{ m.label }}
                    </span>
                  </button>
                </div>
              </div>

              <!-- Conditional: Bank / Transaction fields -->
              <div v-if="needsTransactionRef" class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <div v-if="payment_method === 'bank_transfer' || payment_method === 'cheque'">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                  <input v-model="bank_name" type="text"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="e.g., HBL, MCB, Meezan" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    {{ payment_method === 'cheque' ? 'Cheque No.' : 'Transaction ID' }}
                  </label>
                  <input v-model="transaction_ref" type="text"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-mono"
                    :placeholder="payment_method === 'cheque' ? 'e.g., 001234' : 'Transaction reference'" />
                </div>
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes <span class="text-gray-400 font-normal">(optional)</span></label>
                <input v-model="notes" type="text"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                  placeholder="Koi extra note..." />
              </div>

              <!-- Payment Summary -->
              <div class="bg-gray-50 rounded-lg border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Payment Summary</p>

                <!-- Advance Summary -->
                <div v-if="isAdvance" class="space-y-2">
                  <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center gap-2">
                      <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Advance</span>
                      <span class="font-medium text-gray-800">Advance Payment</span>
                    </div>
                    <div class="text-right">
                      <div class="font-semibold text-gray-900">Rs. {{ Number(advanceAmount || 0).toLocaleString() }}</div>
                    </div>
                  </div>
                </div>

                <!-- Regular Summary -->
                <div v-else class="space-y-2">
                    <div v-for="v in selectedVouchers" :key="v.id" class="flex items-center justify-between text-sm">
                      <div class="min-w-0">
                        <div class="flex items-center gap-2">
                          <span class="font-medium text-gray-800">{{ v.fee_type }}</span>
                          <span class="ml-2 text-xs text-gray-400 font-mono">{{ v.voucher_no }}</span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          <span>Total: Rs. <span class="font-medium text-gray-700">{{ Number(v.net_amount || 0).toLocaleString() }}</span></span>
                          <span class="mx-2">·</span>
                          <span>Paid: Rs. <span class="font-medium text-green-700">{{ Number(v.paid_amount || 0).toLocaleString() }}</span></span>
                          <span class="mx-2">·</span>
                          <span>Remaining: Rs. <span class="font-medium text-red-600">{{ Number(v.remaining_amount || 0).toLocaleString() }}</span></span>
                        </div>
                      </div>
                      <div class="ml-4 flex-shrink-0 text-right">
                        <div class="font-semibold text-gray-900">Rs. {{ Number(v.customAmount || 0).toLocaleString() }}</div>
                        <div class="text-xs text-gray-400">to pay</div>
                      </div>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-gray-200 flex items-center justify-between">
                  <div class="text-sm text-gray-600">
                    <span class="font-medium">{{ selectedStudent?.student_name }}</span>
                    <span class="mx-1 text-gray-300">·</span>
                    <span v-if="isAdvance" class="text-blue-600 font-medium">Advance Payment</span>
                    <span v-else class="text-gray-400">{{ selectedVouchers.length }} voucher{{ selectedVouchers.length > 1 ? 's' : '' }}</span>
                  </div>
                  <div class="text-right">
                    <p class="text-lg font-bold text-indigo-600">Rs. {{ (isAdvance ? Number(advanceAmount || 0) : totalSelected).toLocaleString() }}</p>
                    <p class="text-xs text-gray-400">total</p>
                  </div>
                </div>
              </div>

              <!-- Submit -->
              <div class="flex gap-3 justify-end">
                <Button type="button" variant="secondary" @click="$inertia.visit(route('fee-payments.index'))" class="text-sm">Cancel</Button>
                <Button type="submit" variant="primary" :loading="processing" class="text-sm px-8">
                  <svg v-if="!processing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <span v-if="!processing">Confirm Payment</span>
                  <span v-else>Processing...</span>
                </Button>
              </div>

            </form>
          </div>

        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'
import axios from 'axios'

const page = usePage()

// ── Search State ──────────────────────────────────────────────────────────────
const searchQuery     = ref('')
const searchResults   = ref([])
const searchLoading   = ref(false)
const showDropdown    = ref(false)
const selectedStudent = ref(null)
const searchContainer = ref(null)
let searchTimer = null

// ── Voucher State ─────────────────────────────────────────────────────────────
const pendingVouchers  = ref([])
const vouchersLoading  = ref(false)
const selectedVouchers = ref([]) // [{ ...voucher, customAmount }]

// ── Advance State ─────────────────────────────────────────────────────────────
const isAdvance     = ref(false)
const advanceAmount = ref(null)

// ── Form State ────────────────────────────────────────────────────────────────
const processing    = ref(false)
const submitErrors  = ref({})
const payment_date  = ref(new Date().toISOString().split('T')[0])
const payment_method = ref('cash')
const bank_name     = ref('')
const transaction_ref = ref('')
const notes         = ref('')

// ── Computed ─────────────────────────────────────────────────────────────────
const flashError = computed(() => page.props.flash?.error)

const allSelected = computed(() =>
  pendingVouchers.value.length > 0 &&
  selectedVouchers.value.length === pendingVouchers.value.length
)

const partialSelected = computed(() =>
  selectedVouchers.value.length > 0 && selectedVouchers.value.length < pendingVouchers.value.length
)

const totalSelected = computed(() =>
  selectedVouchers.value.reduce((sum, v) => sum + Number(v.customAmount || 0), 0)
)

const needsTransactionRef = computed(() =>
  ['bank_transfer', 'jazzcash', 'easypaisa', 'cheque', 'online'].includes(payment_method.value)
)

const isVoucherSelected = (id) => selectedVouchers.value.some(v => v.id === id)

const getSelectedVoucher = (id) => selectedVouchers.value.find(v => v.id === id)

// ── Payment Methods ───────────────────────────────────────────────────────────
const paymentMethods = [
  { value: 'cash',          label: 'Cash',      icon: '💵', color: 'green'   },
  { value: 'bank_transfer', label: 'Bank',       icon: '🏦', color: 'blue'    },
  { value: 'jazzcash',      label: 'JazzCash',   icon: '📱', color: 'red'     },
  { value: 'easypaisa',     label: 'Easypaisa',  icon: '📲', color: 'emerald' },
  { value: 'cheque',        label: 'Cheque',     icon: '📄', color: 'purple'  },
]

// ── Actions ───────────────────────────────────────────────────────────────────
const onSearchInput = () => {
  clearTimeout(searchTimer)
  showDropdown.value = false
  if (searchQuery.value.length < 2) { searchResults.value = []; return }
  searchLoading.value = true
  searchTimer = setTimeout(async () => {
    try {
      const res = await axios.get(route('fee-payments.search-students'), { params: { q: searchQuery.value } })
      searchResults.value = res.data
      showDropdown.value = true
    } catch {
      searchResults.value = []
    } finally {
      searchLoading.value = false
    }
  }, 300)
}

const selectStudent = async (student) => {
  selectedStudent.value  = student
  searchQuery.value      = student.student_name
  showDropdown.value     = false
  selectedVouchers.value = []

  vouchersLoading.value = true
  try {
    const res = await axios.get(route('fee-payments.pending-vouchers', student.id))
    pendingVouchers.value = res.data
  } catch {
    pendingVouchers.value = []
  } finally {
    vouchersLoading.value = false
  }
}

const clearStudent = () => {
  selectedStudent.value  = null
  selectedVouchers.value = []
  pendingVouchers.value  = []
  searchQuery.value      = ''
  isAdvance.value        = false
  advanceAmount.value    = null
}

const toggleAdvanceMode = () => {
  isAdvance.value = !isAdvance.value
  if (isAdvance.value) {
    selectedVouchers.value = []
  } else {
    advanceAmount.value = null
  }
}

const toggleVoucher = (voucher) => {
  const idx = selectedVouchers.value.findIndex(v => v.id === voucher.id)
  if (idx >= 0) {
    selectedVouchers.value.splice(idx, 1)
  } else {
    selectedVouchers.value.push({
      ...voucher,
      net_amount: Number(voucher.net_amount || 0),
      paid_amount: Number(voucher.paid_amount || 0),
      remaining_amount: Number(voucher.remaining_amount || 0),
      customAmount: Number(voucher.remaining_amount || 0),
    })
  }
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedVouchers.value = []
  } else {
    selectedVouchers.value = pendingVouchers.value.map(v => ({
      ...v,
      net_amount: Number(v.net_amount || 0),
      paid_amount: Number(v.paid_amount || 0),
      remaining_amount: Number(v.remaining_amount || 0),
      customAmount: Number(v.remaining_amount || 0),
    }))
  }
}

const updateAmount = (id, value) => {
  const v = selectedVouchers.value.find(sv => sv.id === id)
  if (v) v.customAmount = value
}

const submit = () => {
  if (isAdvance.value) {
    if (!advanceAmount.value || advanceAmount.value <= 0 || processing.value) return

    submitErrors.value = {}
    processing.value   = true

    router.post(route('fee-payments.store'), {
      voucher_id:            pendingVouchers.value[0]?.id || selectedVouchers.value[0]?.id || 0,
      student_enrollment_id: selectedStudent.value?.id,
      paid_amount:           Number(advanceAmount.value),
      payment_date:          payment_date.value,
      payment_method:        payment_method.value,
      bank_name:             bank_name.value || null,
      transaction_ref:       transaction_ref.value || null,
      is_advance:            true,
      notes:                 notes.value ? `[ADVANCE] ${notes.value}` : '[ADVANCE] Advance payment',
    }, {
      preserveScroll: true,
      onSuccess: () => router.visit(route('fee-payments.index')),
      onError:  (errors) => { submitErrors.value = errors },
      onFinish: () => { processing.value = false },
    })
    return
  }

  if (selectedVouchers.value.length === 0 || processing.value) return

  submitErrors.value = {}
  processing.value   = true

  router.post(route('fee-payments.store-multiple'), {
    student_enrollment_id: selectedStudent.value?.id,
    vouchers: selectedVouchers.value.map(v => ({
      voucher_id:  Number(v.id),
      paid_amount: Number(v.customAmount),
    })),
    payment_date:    payment_date.value,
    payment_method:  payment_method.value,
    bank_name:       bank_name.value || null,
    transaction_ref: transaction_ref.value || null,
    notes:           notes.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => router.visit(route('fee-payments.index')),
    onError:  (errors) => { submitErrors.value = errors },
    onFinish: () => { processing.value = false },
  })
}

// Close dropdown on outside click
document.addEventListener('click', (e) => {
  if (searchContainer.value && !searchContainer.value.contains(e.target)) {
    showDropdown.value = false
  }
})
</script>
