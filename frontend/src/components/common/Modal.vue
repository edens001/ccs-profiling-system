<template>
  <div class="modal fade" :id="id" tabindex="-1" :aria-labelledby="`${id}Label`" aria-hidden="true">
    <div class="modal-dialog" :class="size">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" :id="`${id}Label`">
            <i :class="icon" v-if="icon"></i>
            {{ title }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <slot></slot>
        </div>
        <div class="modal-footer" v-if="showFooter">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Cancel
          </button>
          <button type="button" class="btn btn-primary" @click="$emit('confirm')">
            <i class="bi bi-check-circle"></i> Confirm
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { Modal } from 'bootstrap'

export default {
  name: 'AppModal',
  props: {
    id: {
      type: String,
      required: true
    },
    title: {
      type: String,
      default: 'Modal Title'
    },
    icon: {
      type: String,
      default: ''
    },
    size: {
      type: String,
      default: '',
      validator: (value) => ['modal-sm', 'modal-lg', 'modal-xl', ''].includes(value)
    },
    showFooter: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    show() {
      const modal = new Modal(document.getElementById(this.id))
      modal.show()
    },
    
    hide() {
      const modal = Modal.getInstance(document.getElementById(this.id))
      if (modal) {
        modal.hide()
      }
    }
  }
}
</script>