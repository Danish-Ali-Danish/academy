<template>
  <AppLayout>
    <div class="min-h-screen flex flex-col">
      <div class="flex-1 px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">

        <!-- Header -->
        <div class="mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Add Payment Account</h1>
              <p class="mt-1 text-sm text-gray-500">Academy mein kaunse tareeqe se fees leni hai — setup karo</p>
            </div>
            <Button @click="$inertia.visit(route('academy-payment-accounts.index'))" variant="secondary" class="w-full sm:w-auto text-sm">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              Back to List
            </Button>
          </div>
        </div>

        <div class="max-w-2xl">

          <!-- Step 1: Method Selection -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-4">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Payment Method chuno</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

              <!-- Cash -->
              <button type="button" @click="selectMethod('cash')"
                :class="form.payment_method === 'cash'
                  ? 'border-2 border-green-500 bg-green-50 ring-2 ring-green-200'
                  : 'border-2 border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                class="relative flex flex-col items-center justify-center p-4 rounded-xl transition-all cursor-pointer">
                <div :class="form.payment_method === 'cash' ? 'bg-green-100' : 'bg-gray-100'" class="w-12 h-12 rounded-full flex items-center justify-center mb-2">
                  <svg class="w-6 h-6" :class="form.payment_method === 'cash' ? 'text-green-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                  </svg>
                </div>
                <span class="text-sm font-semibold" :class="form.payment_method === 'cash' ? 'text-green-700' : 'text-gray-700'">Cash</span>
                <span class="text-xs mt-0.5" :class="form.payment_method === 'cash' ? 'text-green-500' : 'text-gray-400'">Sabse zyada</span>
                <svg v-if="form.payment_method === 'cash'" class="absolute top-2 right-2 w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
              </button>

              <!-- Bank Transfer -->
              <button type="button" @click="selectMethod('bank_transfer')"
                :class="form.payment_method === 'bank_transfer'
                  ? 'border-2 border-blue-500 bg-blue-50 ring-2 ring-blue-200'
                  : 'border-2 border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                class="relative flex flex-col items-center justify-center p-4 rounded-xl transition-all cursor-pointer">
                <div :class="form.payment_method === 'bank_transfer' ? 'bg-blue-100' : 'bg-gray-100'" class="w-12 h-12 rounded-full flex items-center justify-center mb-2">
                  <svg class="w-6 h-6" :class="form.payment_method === 'bank_transfer' ? 'text-blue-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                  </svg>
                </div>
                <span class="text-sm font-semibold" :class="form.payment_method === 'bank_transfer' ? 'text-blue-700' : 'text-gray-700'">Bank</span>
                <span class="text-xs mt-0.5" :class="form.payment_method === 'bank_transfer' ? 'text-blue-500' : 'text-gray-400'">Transfer</span>
                <svg v-if="form.payment_method === 'bank_transfer'" class="absolute top-2 right-2 w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
              </button>

              <!-- JazzCash -->
              <button type="button" @click="selectMethod('jazzcash')"
                :class="form.payment_method === 'jazzcash'
                  ? 'border-2 border-red-500 bg-red-50 ring-2 ring-red-200'
                  : 'border-2 border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                class="relative flex flex-col items-center justify-center p-4 rounded-xl transition-all cursor-pointer">
                <div :class="form.payment_method === 'jazzcash' ? 'bg-red-100' : 'bg-gray-100'" class="w-12 h-12 rounded-full flex items-center justify-center mb-2">
                  <svg class="w-6 h-6" :class="form.payment_method === 'jazzcash' ? 'text-red-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                </div>
                <span class="text-sm font-semibold" :class="form.payment_method === 'jazzcash' ? 'text-red-700' : 'text-gray-700'">JazzCash</span>
                <span class="text-xs mt-0.5" :class="form.payment_method === 'jazzcash' ? 'text-red-500' : 'text-gray-400'">Mobile</span>
                <svg v-if="form.payment_method === 'jazzcash'" class="absolute top-2 right-2 w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
              </button>

              <!-- Easypaisa -->
              <button type="button" @click="selectMethod('easypaisa')"
                :class="form.payment_method === 'easypaisa'
                  ? 'border-2 border-emerald-500 bg-emerald-50 ring-2 ring-emerald-200'
                  : 'border-2 border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'"
                class="relative flex flex-col items-center justify-center p-4 rounded-xl transition-all cursor-pointer">
                <div :class="form.payment_method === 'easypaisa' ? 'bg-emerald-100' : 'bg-gray-100'" class="w-12 h-12 rounded-full flex items-center justify-center mb-2">
                  <svg class="w-6 h-6" :class="form.payment_method === 'easypaisa' ? 'text-emerald-600' : 'text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                </div>
                <span class="text-sm font-semibold" :class="form.payment_method === 'easypaisa' ? 'text-emerald-700' : 'text-gray-700'">Easypaisa</span>
                <span class="text-xs mt-0.5" :class="form.payment_method === 'easypaisa' ? 'text-emerald-500' : 'text-gray-400'">Mobile</span>
                <svg v-if="form.payment_method === 'easypaisa'" class="absolute top-2 right-2 w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
              </button>

            </div>
            <p v-if="form.errors.payment_method" class="mt-2 text-sm text-red-600">{{ form.errors.payment_method }}</p>
          </div>

          <!-- Step 2: Details (method ke hisaab se) -->
          <form @submit.prevent="submit">

            <!-- CASH: Simple card, koi required field nahi -->
            <div v-if="form.payment_method === 'cash'" class="bg-white rounded-xl shadow-sm border border-green-200 p-5 mb-4">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">Cash Payment</h3>
                  <p class="text-xs text-gray-500">Account number ki zaroorat nahi — direct collection</p>
                </div>
              </div>
              <div class="grid grid-cols-1 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Label <span class="text-gray-400 font-normal">(optional)</span>
                  </label>
                  <input v-model="form.account_title" type="text"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                    placeholder="e.g., Office Cash, Front Desk, Branch 2 Cash" />
                  <p class="mt-1 text-xs text-gray-400">Khali chhoro to automatically "Cash" save ho jayega</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">
                    Instructions <span class="text-gray-400 font-normal">(optional)</span>
                  </label>
                  <textarea v-model="form.instructions" rows="2"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                    placeholder="e.g., Office mein jama karein, 9am-2pm..."></textarea>
                </div>
              </div>
            </div>

            <!-- BANK TRANSFER -->
            <div v-if="form.payment_method === 'bank_transfer'" class="bg-white rounded-xl shadow-sm border border-blue-200 p-5 mb-4">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                  <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">Bank Transfer</h3>
                  <p class="text-xs text-gray-500">Bank account ki details dalo</p>
                </div>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Account Title <span class="text-red-500">*</span></label>
                  <input v-model="form.account_title" type="text"
                    :class="{ 'border-red-500': form.errors.account_title }"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="e.g., Academy Main Account" />
                  <p v-if="form.errors.account_title" class="mt-1 text-xs text-red-600">{{ form.errors.account_title }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name <span class="text-red-500">*</span></label>
                  <input v-model="form.bank_name" type="text"
                    :class="{ 'border-red-500': form.errors.bank_name }"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="e.g., HBL, MCB, UBL, Meezan" />
                  <p v-if="form.errors.bank_name" class="mt-1 text-xs text-red-600">{{ form.errors.bank_name }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Account Number <span class="text-red-500">*</span></label>
                  <input v-model="form.account_number" type="text"
                    :class="{ 'border-red-500': form.errors.account_number }"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono sm:text-sm"
                    placeholder="e.g., 01234567890123" />
                  <p v-if="form.errors.account_number" class="mt-1 text-xs text-red-600">{{ form.errors.account_number }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Branch Name <span class="text-gray-400 font-normal">(optional)</span></label>
                  <input v-model="form.branch_name" type="text"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="e.g., Main Branch, Gulshan" />
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">IBAN <span class="text-gray-400 font-normal">(optional)</span></label>
                  <input v-model="form.iban" type="text"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 font-mono sm:text-sm"
                    placeholder="e.g., PK36SCBL0000001123456702" />
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Payment Instructions <span class="text-gray-400 font-normal">(optional)</span></label>
                  <textarea v-model="form.instructions" rows="2"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="e.g., Transaction ID receipt office mein jama karein"></textarea>
                </div>
              </div>
            </div>

            <!-- JAZZCASH / EASYPAISA -->
            <div v-if="form.payment_method === 'jazzcash' || form.payment_method === 'easypaisa'"
              :class="form.payment_method === 'jazzcash' ? 'border-red-200' : 'border-emerald-200'"
              class="bg-white rounded-xl shadow-sm border p-5 mb-4">
              <div class="flex items-center gap-3 mb-4">
                <div :class="form.payment_method === 'jazzcash' ? 'bg-red-100' : 'bg-emerald-100'" class="w-10 h-10 rounded-full flex items-center justify-center">
                  <svg class="w-5 h-5" :class="form.payment_method === 'jazzcash' ? 'text-red-600' : 'text-emerald-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ form.payment_method === 'jazzcash' ? 'JazzCash' : 'Easypaisa' }}</h3>
                  <p class="text-xs text-gray-500">Mobile wallet account ki details</p>
                </div>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name <span class="text-red-500">*</span></label>
                  <input v-model="form.account_title" type="text"
                    :class="[
                      { 'border-red-500': form.errors.account_title },
                      form.payment_method === 'jazzcash' ? 'focus:border-red-500 focus:ring-red-500' : 'focus:border-emerald-500 focus:ring-emerald-500'
                    ]"
                    class="block w-full rounded-lg border-gray-300 shadow-sm sm:text-sm"
                    placeholder="e.g., Muhammad Ali" />
                  <p v-if="form.errors.account_title" class="mt-1 text-xs text-red-600">{{ form.errors.account_title }}</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                  <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">+92</span>
                    <input v-model="form.account_number" type="text"
                      :class="{ 'border-red-500': form.errors.account_number }"
                      class="block w-full rounded-r-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono sm:text-sm"
                      placeholder="3001234567" maxlength="10" />
                  </div>
                  <p v-if="form.errors.account_number" class="mt-1 text-xs text-red-600">{{ form.errors.account_number }}</p>
                </div>
                <div class="sm:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-1">Instructions <span class="text-gray-400 font-normal">(optional)</span></label>
                  <textarea v-model="form.instructions" rows="2"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="e.g., Payment ke baad screenshot WhatsApp par bhejein"></textarea>
                </div>
              </div>
            </div>

            <!-- Status + Actions -->
            <div v-if="form.payment_method" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
              <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <label class="flex items-center gap-3 cursor-pointer">
                  <div class="relative">
                    <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                    <div class="w-10 h-6 bg-gray-200 peer-checked:bg-indigo-600 rounded-full transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-gray-900">Account Active</span>
                    <p class="text-xs text-gray-400">Inactive accounts fee collection mein show nahi honge</p>
                  </div>
                </label>
                <div class="flex gap-3 w-full sm:w-auto">
                  <Button type="button" variant="secondary" @click="$inertia.visit(route('academy-payment-accounts.index'))" class="flex-1 sm:flex-none text-sm">Cancel</Button>
                  <Button type="submit" variant="primary" :loading="form.processing" class="flex-1 sm:flex-none text-sm">
                    <span v-if="!form.processing">Save Account</span>
                    <span v-else>Saving...</span>
                  </Button>
                </div>
              </div>
            </div>

            <!-- Placeholder when no method selected -->
            <div v-if="!form.payment_method" class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-8 text-center">
              <svg class="w-10 h-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
              </svg>
              <p class="text-sm text-gray-500">Upar se payment method chuno to details form dikhega</p>
            </div>

          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import Button from '@/Components/Common/Button.vue'

const form = useForm({
  account_title:  '',
  payment_method: '',
  account_number: '',
  bank_name:      '',
  branch_name:    '',
  iban:           '',
  instructions:   '',
  is_active:      true,
})

const selectMethod = (method) => {
  form.payment_method = method
  form.account_title  = ''
  form.account_number = ''
  form.bank_name      = ''
  form.branch_name    = ''
  form.iban           = ''
  form.instructions   = ''
  form.clearErrors()
}

const submit = () => {
  form.post(route('academy-payment-accounts.store'), { preserveScroll: true })
}
</script>
