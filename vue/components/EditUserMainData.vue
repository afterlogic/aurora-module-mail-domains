<template>
  <div>
    <div class="row q-mb-md" v-if="!createMode">
      <div class="col-1 q-my-sm" v-t="'COREWEBCLIENT.LABEL_EMAIL'"></div>
      <div class="col-5">
        <q-input outlined dense bg-color="white" v-model="publicId" ref="publicId" :disable="!createMode"
                 @keyup.enter="save" />
      </div>
    </div>
    <div class="row q-mb-md" v-if="createMode">
      <div class="col-1 q-my-sm" v-t="'COREWEBCLIENT.LABEL_EMAIL'"></div>
      <div class="col-3">
        <q-input outlined dense bg-color="white" v-model="publicId" ref="publicId" :disable="!createMode"
                 @keyup.enter="save" />
      </div>
      <div class="col-3 q-ml-sm">
        <q-select outlined dense bg-color="white" v-model="selectedDomain"
                  emit-value map-options :options="domains" option-label="name" />
      </div>
    </div>
    <div class="row q-mb-md" v-if="createMode">
      <div class="col-1 q-my-sm" v-t="'COREWEBCLIENT.LABEL_PASSWORD'"></div>
      <div class="col-3">
        <q-input outlined dense bg-color="white" v-model="password" ref="password" type="password"
                 autocomplete="new-password" @keyup.enter="save"/>
      </div>
    </div>
  </div>
</template>

<script>
import _ from 'lodash'

import notification from 'src/utils/notification'

import cache from '../cache'

export default {
  name: 'EditUserPublicId',

  props: {
    user: Object,
    createMode: Boolean,
    currentTenantId: Number,
  },

  data () {
    return {
      publicId: '',
      password: '',
      domains: [],
      selectedDomain: null,
    }
  },

  watch: {
    user () {
      this.publicId = this.user?.publicId
    },
  },

  mounted () {
    this.populate()
  },

  methods: {
    populate () {
      this.publicId = this.user?.publicId
      this.password = ''
      this.domains = []
      cache.getDomains(this.currentTenantId).then(({ domains, totalCount, tenantId }) => {
        if (tenantId === this.currentTenantId) {
          this.domains = domains
          if (this.domains.length > 0) {
            this.selectedDomain = this.domains[0]
          }
        }
      })
    },
    getSaveParameters () {
      const parameters = {
        PublicId: this.createMode ? this.publicId + '@' + this.selectedDomain?.name : this.user?.publicId,
        DomainId: this.selectedDomain?.id
      }
      if (this.createMode) {
        parameters.Password = this.password
      }
      return parameters
    },

    hasChanges () {
      if (this.createMode) {
        let publicId = this.publicId
        if (!_.isEmpty(publicId)) {
          publicId += '@' + this.selectedDomain?.name
        }
        return publicId !== this.user?.publicId
      } else {
        return this.publicId !== this.user?.publicId
      }
    },

    isDataValid () {
      if (this.createMode) {
        const publicId = _.trim(this.publicId)
        if (publicId === '') {
          notification.showError(this.$t('ADMINPANELWEBCLIENT.ERROR_USER_NAME_EMPTY'))
          this.$refs.publicId.$el.focus()
          return false
        }
        const password = _.trim(this.password)
        if (password === '') {
          notification.showError(this.$t('MAILDOMAINS.ERROR_PASSWORD_EMPTY'))
          this.$refs.password.$el.focus()
          return false
        }
      }
      return true
    },

    save () {
      this.$emit('save')
    },
  },
}
</script>

<style scoped>

</style>
