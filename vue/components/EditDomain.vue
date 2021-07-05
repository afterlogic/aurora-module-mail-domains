<template>
  <q-scroll-area class="full-height full-width relative-position">
    <div class="q-pa-lg ">
      <div class="row q-mb-md">
        <div class="col text-h5" v-if="!createMode" v-t="'MAILDOMAINS.HEADING_EDIT_MAILDOMAIN'"></div>
        <div class="col text-h5" v-if="createMode" v-t="'MAILDOMAINS.HEADING_ADD_MAILDOMAIN'"></div>
      </div>
      <q-card flat bordered class="card-edit-settings">
        <q-card-section v-if="!createMode">
          <div class="row q-mb-md">
            <div class="col-1" v-t="'MAILDOMAINS.LABEL_MAILDOMAIN'"></div>
            <div class="col-5">{{ domainName }}</div>
          </div>
          <div class="row q-mb-md" v-if="domainMailServer">
            <div class="col-1" v-t="'MAILDOMAINS.LABEL_MAIL_SERVER'"></div>
            <div class="col-5">{{ domainMailServer }}</div>
          </div>
          <div class="row">
            <div class="col-1" v-t="'MAILDOMAINS.LABEL_MAILDOMAIN_USERS_COUNT'"></div>
            <div class="col-5">{{ domainUserCount }}</div>
          </div>
<!--          <div class="row" v-if="domain">-->
<!--            <div class="col-6">-->
<!--              <router-link :to="'/users/domain/' + domain.id" :ripple="false" class="q-px-none"-->
<!--                           v-t="'MAILDOMAINS.ACTION_SHOW_DOMAIN_USERS'"></router-link>-->
<!--            </div>-->
<!--          </div>-->
        </q-card-section>
        <q-card-section v-if="createMode">
          <div class="row q-mb-md">
            <div class="col-1" v-t="'MAILDOMAINS.LABEL_MAILDOMAIN'"></div>
            <div class="col-5">
              <q-input outlined dense class="bg-white" v-model="domainName" ref="domainName" @keyup.enter="create" />
            </div>
          </div>
          <div class="row q-mb-md">
            <div class="col-1" v-t="'MAILDOMAINS.LABEL_MAIL_SERVER'"></div>
            <div class="col-5">
              <q-select outlined dense class="bg-white" v-model="selectedServerId"
                        emit-value map-options :options="serverOptions" />
            </div>
          </div>
        </q-card-section>
      </q-card>
      <div class="q-pa-md text-right">
        <q-btn unelevated no-caps dense class="q-px-sm" :ripple="false" color="primary" @click="deleteDomain"
               :label="$t('MAILDOMAINS.ACTION_DELETE_MAILDOMAIN')" v-if="!createMode">
        </q-btn>
        <q-btn unelevated no-caps dense class="q-px-sm q-ml-sm" :ripple="false" color="primary" @click="create"
               :label="creating ? $t('COREWEBCLIENT.ACTION_CREATE_IN_PROGRESS') : $t('MAILDOMAINS.ACTION_ADD')" v-if="createMode">
        </q-btn>
        <q-btn unelevated no-caps dense class="q-px-sm q-ml-sm" :ripple="false" color="secondary" @click="cancel"
               :label="$t('COREWEBCLIENT.ACTION_CANCEL')" v-if="createMode" >
        </q-btn>
      </div>
    </div>
    <UnsavedChangesDialog ref="unsavedChangesDialog" />
    <q-inner-loading :showing="loading || deleting || creating">
      <q-spinner size="50px" color="primary" />
    </q-inner-loading>
  </q-scroll-area>
</template>

<script>
import _ from 'lodash'

import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'

import cache from '../cache'

import DomainModel from '../classes/domain'

import UnsavedChangesDialog from 'src/components/UnsavedChangesDialog'

export default {
  name: 'EditDomain',

  components: {
    UnsavedChangesDialog,
  },

  props: {
    deletingIds: Array,
  },

  data() {
    return {
      selectedServerId: null,

      domain: null,
      domainName: '',
      domainMailServerId: '',
      domainUserCount: 0,

      loading: false,
      creating: false,
    }
  },

  computed: {
    currentTenantId () {
      return this.$store.getters['tenants/getCurrentTenantId']
    },

    serverOptions () {
      const serversByTenants = this.$store.getters['mail/getServersByTenants']
      const tenantServers = typesUtils.pArray(serversByTenants[this.currentTenantId])
      return tenantServers.map(server => {
        return {
          value: server.id,
          label: server.name,
        }
      })
    },

    domainMailServer () {
      const option = this.serverOptions.find(option => option.value === this.domainMailServerId)
      return option?.label || ''
    },

    createMode () {
      return this.domain?.id === 0
    },

    deleting () {
      return this.deletingIds.indexOf(this.domain?.id) !== -1
    },
  },

  watch: {
    $route(to, from) {
      this.parseRoute()
    },

    serverOptions () {
      this.setSelectedServerId()
    },
  },

  beforeRouteLeave (to, from, next) {
    if (this.hasChanges() && _.isFunction(this?.$refs?.unsavedChangesDialog?.openConfirmDiscardChangesDialog)) {
      this.$refs.unsavedChangesDialog.openConfirmDiscardChangesDialog(next)
    } else {
      next()
    }
  },

  beforeRouteUpdate (to, from, next) {
    if (this.hasChanges() && _.isFunction(this?.$refs?.unsavedChangesDialog?.openConfirmDiscardChangesDialog)) {
      this.$refs.unsavedChangesDialog.openConfirmDiscardChangesDialog(next)
    } else {
      next()
    }
  },

  mounted () {
    this.$store.dispatch('mail/requestTenantServers', this.currentTenantId)
    this.loading = false
    this.creating = false
    this.parseRoute()
  },

  methods: {
    parseRoute () {
      if (this.$route.path === '/domains/create') {
        const domain = new DomainModel({ TenantId: this.currentTenantId })
        this.fillUp(domain)
      } else {
        const domainId = typesUtils.pPositiveInt(this.$route?.params?.id)
        if (this.domain?.id !== domainId) {
          this.domain = {
            id: domainId,
          }
          this.populate()
        }
      }
    },

    clear () {
      this.domainName = ''
      this.domainUserCount = 0
      this.setSelectedServerId()
    },

    setSelectedServerId () {
      const isOptionsEmpty = this.serverOptions.length === 0
      if (isOptionsEmpty) {
        this.selectedServerId = null
      } else if (!this.serverOptions.find(option => option.value === this.selectedServerId)) {
        this.selectedServerId = this.serverOptions[0].value
      }
    },

    fillUp (domain) {
      this.domain = domain
      this.domainName = domain.name
      this.domainMailServerId = domain.mailServerId
      this.domainUserCount = domain.count
    },

    populate () {
      this.clear()
      this.loading = true
      cache.getDomain(this.currentTenantId, this.domain.id).then(({ domain, domainId }) => {
        if (domainId === this.domain.id) {
          this.loading = false
          if (domain) {
            this.fillUp(domain)
          } else {
            this.$emit('no-domain-found')
          }
        }
      })
    },

    hasChanges () {
      return this.domainName !== this.domain?.name
    },

    isDataValid () {
      const domainName = _.trim(this.domainName)
      if (domainName === '') {
        notification.showError(this.$t('MAILDOMAINS.ERROR_MAILDOMAIN_NAME_EMPTY'))
        this.$refs.domainName.$el.focus()
        return false
      }
      return true
    },

    create () {
      if (this.createMode && !this.creating && this.isDataValid()) {
        this.creating = true
        const parameters = {
          DomainName: this.domainName,
          MailServerId: this.selectedServerId,
          TenantId: this.currentTenantId,
        }

        webApi.sendRequest({
          moduleName: 'MailDomains',
          methodName: 'CreateDomain',
          parameters,
        }).then(result => {
          this.creating = false
          if (_.isSafeInteger(result)) {
            notification.showReport(this.$t('MAILDOMAINS.REPORT_ADD_ENTITY_MAILDOMAIN'))
            this.domain = new DomainModel({
              TenantId: this.currentTenantId,
              MailServerId: parameters.MailServerId,
              Name: parameters.DomainName
            })
            this.$emit('domain-created', result)
          } else {
            notification.showError(this.$t('MAILDOMAINS.ERROR_ADD_ENTITY_MAILDOMAIN'))
          }
        }, response => {
          this.creating = false
          notification.showError(errors.getTextFromResponse(response, this.$t('MAILDOMAINS.ERROR_ADD_ENTITY_MAILDOMAIN')))
        })
      }
    },

    cancel () {
      this.$emit('cancel-create')
    },

    deleteDomain () {
      this.$emit('delete-domain', this.domain.id)
    },
  },
}
</script>

<style scoped>

</style>
