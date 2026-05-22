<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="px-3 sm:px-4 lg:px-8 py-4 sm:py-6 lg:py-8">
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Review Payment Proof</h1>
            <p class="mt-1 text-sm text-gray-500">Verify or reject the submitted online payment proof.</p>
          </div>
          <Button @click="$inertia.visit(route('online-payment-proofs.index'))" variant="secondary" class="w-full sm:w-auto text-sm">Back</Button>
        </div>

        <div class="max-w-4xl space-y-4">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-sm">
              <div class="rounded-lg bg-indigo-50 p-3"><div class="text-xs text-indigo-500">Voucher</div><div class="font-semibold text-indigo-900">{{ props.proof.voucher_id }}</div></div>
              <div class="rounded-lg bg-indigo-50 p-3"><div class="text-xs text-indigo-500">Student</div><div class="font-semibold text-indigo-900">{{ props.proof.student_enrollment_id }}</div></div>
              <div class="rounded-lg bg-indigo-50 p-3"><div class="text-xs text-indigo-500">Amount</div><div class="font-semibold text-indigo-900">Rs. {{ Number(props.proof.amount_sent || 0).toLocaleString() }}</div></div>
              <div class="rounded-lg bg-indigo-50 p-3"><div class="text-xs text-indigo-500">Method</div><div class="font-semibold text-indigo-900">{{ props.proof.payment_method }}</div></div>
            </div>
          </div>

          <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                <select v-model="form.verification_status" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                  <option value="pending">Pending</option>
                  <option value="verified">Verified</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason</label>
                <input v-model="form.rejection_reason" type="text" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" placeholder="Only needed if rejected" />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
              <textarea v-model="form.submission_notes" rows="4" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"></textarea>
            </div>

            <div class="flex justify-end gap-3">
              <Button type="button" variant="secondary" @click="$inertia.visit(route('online-payment-proofs.index'))">Cancel</Button>
              <Button type="submit" variant="primary" :loading="form.processing">Save Changes</Button>
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

const props = defineProps({
  proof: { type: Object, required: true },
  vouchers: { type: Array, default: () => [] },
  accounts: { type: Array, default: () => [] },
})

const form = useForm({
  verification_status: props.proof.verification_status || 'pending',
  rejection_reason: props.proof.rejection_reason || '',
  submission_notes: props.proof.submission_notes || '',
})

const submit = () => {
  form.put(route('online-payment-proofs.update', props.proof.id))
}
</script>