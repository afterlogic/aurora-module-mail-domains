<template>
  <div>
    <div class="row q-mb-md" v-if="!createMode">
      <div class="col-2 q-my-sm" v-t="'COREWEBCLIENT.LABEL_EMAIL'"></div>
      <div class="col-5">
        <q-input outlined dense bg-color="white" v-model="publicId" ref="publicId" :disable="!createMode"
                 @keyup.enter="save" />
      </div>
    </div>
    <div class="row q-mb-md items-center" v-if="createMode">
      <div class="col-2" v-t="'COREWEBCLIENT.LABEL_EMAIL'"></div>
      <div class="col-3">
        <q-input outlined dense bg-color="white" v-model="publicId" ref="publicId" :disable="!createMode"
                 @keyup.enter="save" />
      </div>
      <div class="q-ml-sm">@</div>
      <div class="col-3 q-ml-sm" v-if="selectedDomain">
        <q-select outlined dense bg-color="white" v-model="selectedDomain"
                  emit-value map-options :options="domains" option-label="name">
          <template v-slot:selected>
            <div class="ellipsis">{{ selectedDomain.name }}</div>
          </template>
        </q-select>
      </div>
    </div>
    <div class="row q-mb-md" v-if="createMode">
      <div class="col-2 q-my-sm" v-t="'COREWEBCLIENT.LABEL_PASSWORD'"></div>
      <div class="col-3">
        <q-input outlined dense bg-color="white" type="password" autocomplete="new-password"
                 v-model="password" ref="password" @keyup.enter="save"/>
      </div>
    </div>
  </div>
</template>

<script>
import _ from 'lodash'

import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'

export default {
  name: 'EditMailDomainsUserMainData',

  props: {
    user: Object,
    createMode: Boolean,
  },

  data () {
    return {
      publicId: '',
      password: '',
      selectedDomain: null,
    }
  },

  computed: {
    currentTenantId () {
      return this.$store.getters['tenants/getCurrentTenantId']
    },

    domains () {
      const allDomainLists = this.$store.getters['maildomains/getDomains']
      return typesUtils.pArray(allDomainLists[this.currentTenantId])
    }
  },

  watch: {
    user () {
      this.publicId = typesUtils.pString(this.user?.publicId)
    },

    currentTenantId () {
      this.requestDomains()
    },
  },

  mounted () {
    this.requestDomains()
    this.populate()
  },

  methods: {
    requestDomains () {
      this.$store.dispatch('maildomains/requestDomainsIfNecessary', {
        tenantId: this.currentTenantId
      })
    },

    populate () {
      this.publicId = typesUtils.pString(this.user?.publicId)
      this.password = ''
      if (this.selectedDomain === null && this.domains.length > 0) {
        this.selectedDomain = this.domains[0]
      }
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

    /**
     * Method is used in the parent component
     */
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

    /**
     * Method is used in the parent component,
     * do not use async methods - just simple and plain reverting of values
     * !! hasChanges method must return true after executing revertChanges method
     */
    revertChanges () {
      if (this.createMode) {
        this.publicId = ''
      } else {
        this.publicId = typesUtils.pString(this.user?.publicId)
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
