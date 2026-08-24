<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-y-auto">
    <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full my-8">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Order Details</h2>
        <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700 text-2xl">✕</button>
      </div>

      <div v-if="order" class="space-y-6">
        <!-- Order Header -->
        <div class="bg-gray-50 rounded-lg p-4 grid grid-cols-2 gap-4">
          <div>
            <p class="text-gray-600 text-sm">Order Number</p>
            <p class="text-lg font-bold text-blue-600">{{ order.order_number }}</p>
          </div>
          <div>
            <p class="text-gray-600 text-sm">Status</p>
            <span
              :class="{
                'bg-blue-100 text-blue-800': order.status === 'pending',
                'bg-green-100 text-green-800': order.status === 'confirmed',
                'bg-red-100 text-red-800': order.status === 'cancelled'
              }"
              class="px-3 py-1 rounded-full text-sm font-medium"
            >
              {{ order.status }}
            </span>
          </div>
          <div>
            <p class="text-gray-600 text-sm">Created By</p>
            <p class="font-medium">{{ order.created_by?.name }}</p>
          </div>
          <div>
            <p class="text-gray-600 text-sm">Date</p>
            <p class="font-medium">{{ new Date(order.created_at).toLocaleString() }}</p>
          </div>
        </div>

        <!-- Order Items -->
        <div>
          <h3 class="text-lg font-bold mb-3">Order Items</h3>
          <div class="border rounded-lg overflow-hidden">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="px-4 py-2 text-left text-gray-700">Product</th>
                  <th class="px-4 py-2 text-right text-gray-700">Price</th>
                  <th class="px-4 py-2 text-center text-gray-700">Qty</th>
                  <th class="px-4 py-2 text-right text-gray-700">Tax %</th>
                  <th class="px-4 py-2 text-right text-gray-700">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="item in order.items" :key="item.id" class="hover:bg-gray-50">
                  <td class="px-4 py-2 font-medium">{{ item.product?.name }}</td>
                  <td class="px-4 py-2 text-right">${{ parseFloat(item.unit_price).toFixed(2) }}</td>
                  <td class="px-4 py-2 text-center">{{ item.quantity }}</td>
                  <td class="px-4 py-2 text-right">{{ item.tax_percentage }}%</td>
                  <td class="px-4 py-2 text-right font-medium">${{ parseFloat(item.line_total).toFixed(2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Totals -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
          <div class="flex justify-between">
            <span class="text-gray-600">Subtotal:</span>
            <span class="font-medium">${{ parseFloat(order.subtotal).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Tax:</span>
            <span class="font-medium">${{ parseFloat(order.tax_amount).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-lg font-bold border-t pt-2">
            <span>Total Amount:</span>
            <span class="text-blue-600">${{ parseFloat(order.total_amount).toFixed(2) }}</span>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="order.notes">
          <h3 class="text-lg font-bold mb-2">Notes</h3>
          <p class="text-gray-700 bg-gray-50 p-3 rounded-lg">{{ order.notes }}</p>
        </div>

        <div class="flex gap-2 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  order: Object
})

defineEmits(['close'])
</script>

<style scoped></style>
