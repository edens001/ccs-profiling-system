<template>
  <div class="table-responsive">
    <table class="table table-hover" :class="tableClass">
      <thead>
        <tr>
          <th v-for="column in columns" :key="column.key">
            <div class="d-flex align-items-center">
              {{ column.label }}
              <span v-if="column.sortable" class="ms-2 sort-icons" @click="sort(column.key)">
                <i class="bi bi-arrow-up" :class="{ active: sortKey === column.key && sortOrder === 'asc' }"></i>
                <i class="bi bi-arrow-down" :class="{ active: sortKey === column.key && sortOrder === 'desc' }"></i>
              </span>
            </div>
          </th>
          <th v-if="actions.length > 0">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in sortedData" :key="index">
          <td v-for="column in columns" :key="column.key">
            <template v-if="column.type === 'badge'">
              <span :class="'badge bg-' + getBadgeColor(item[column.key])">
                {{ item[column.key] }}
              </span>
            </template>
            <template v-else-if="column.type === 'date'">
              {{ formatDate(item[column.key]) }}
            </template>
            <template v-else-if="column.type === 'currency'">
              {{ formatCurrency(item[column.key]) }}
            </template>
            <template v-else>
              {{ item[column.key] }}
            </template>
          </td>
          <td v-if="actions.length > 0">
            <button v-for="action in actions" :key="action.label"
                    class="btn btn-sm me-1"
                    :class="'btn-' + action.color"
                    @click="$emit('action', { action: action.name, item })">
              <i :class="action.icon"></i>
            </button>
          </td>
        </tr>
        <tr v-if="data.length === 0">
          <td :colspan="columns.length + (actions.length > 0 ? 1 : 0)" class="text-center py-4">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            No data available
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { ref, computed } from 'vue'

export default {
  name: 'DataTable',
  props: {
    data: {
      type: Array,
      required: true
    },
    columns: {
      type: Array,
      required: true
    },
    actions: {
      type: Array,
      default: () => []
    },
    tableClass: {
      type: String,
      default: ''
    }
  },
  emits: ['action'],
  setup(props) {
    const sortKey = ref('')
    const sortOrder = ref('asc')
    
    const getBadgeColor = (value) => {
      const colors = {
        'Active': 'success',
        'Inactive': 'danger',
        'Pending': 'warning',
        'Completed': 'info'
      }
      return colors[value] || 'secondary'
    }
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString()
    }
    
    const formatCurrency = (value) => {
      return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
      }).format(value)
    }
    
    const sort = (key) => {
      if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
      } else {
        sortKey.value = key
        sortOrder.value = 'asc'
      }
    }
    
    const sortedData = computed(() => {
      if (!sortKey.value) return props.data
      
      return [...props.data].sort((a, b) => {
        let aVal = a[sortKey.value]
        let bVal = b[sortKey.value]
        
        if (typeof aVal === 'string') {
          aVal = aVal.toLowerCase()
          bVal = bVal.toLowerCase()
        }
        
        if (aVal < bVal) return sortOrder.value === 'asc' ? -1 : 1
        if (aVal > bVal) return sortOrder.value === 'asc' ? 1 : -1
        return 0
      })
    })
    
    return {
      sortKey,
      sortOrder,
      getBadgeColor,
      formatDate,
      formatCurrency,
      sort,
      sortedData
    }
  }
}
</script>

<style scoped>
.sort-icons {
  cursor: pointer;
  opacity: 0.5;
}

.sort-icons i.active {
  opacity: 1;
  color: #3498db;
}

.sort-icons i:hover {
  opacity: 1;
}
</style>