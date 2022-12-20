<template>
  <main-layout>
    <q-splitter :after-class="!showTabs ? 'q-splitter__right-panel' : ''" class="full-height full-width"
                v-model="listSplitterWidth" :limits="[10,30]"
    >
      <template v-slot:before>
        <div class="flex column full-height">
          <q-toolbar class="col-auto q-py-sm list-border">
            <q-btn flat color="grey-8" size="mg" no-wrap :disable="checkedIds.length === 0"
                   @click="askDeleteCheckedDomains">
              <Trash></Trash>
              <span>{{ countLabel }}</span>
              <q-tooltip>
                {{ $t('COREWEBCLIENT.ACTION_DELETE') }}
              </q-tooltip>
            </q-btn>
            <q-btn flat color="grey-8" size="mg" @click="routeCreateDomain">
              <Add></Add>
              <q-tooltip>
                {{ $t('MAILDOMAINS.ACTION_ADD_ENTITY_MAILDOMAIN') }}
              </q-tooltip>
            </q-btn>
          </q-toolbar>
          <StandardList class="col-grow list-border" :items="domainItems" :selectedItem="selectedDomainId" :loading="loadingDomains"
                        :search="search" :page="page" :pagesCount="pagesCount"
                        :noItemsText="'MAILDOMAINS.INFO_NO_ENTITIES_MAILDOMAIN'"
                        :noItemsFoundText="'MAILDOMAINS.INFO_NO_ENTITIES_FOUND_MAILDOMAIN'"
                        ref="domainList" @route="route" @check="afterCheck"/>
        </div>
      </template>
      <template v-slot:after>
        <q-splitter after-class="q-splitter__right-panel" v-if="showTabs" class="full-height full-width"
                    v-model="tabsSplitterWidth" :limits="[10,30]">
          <template v-slot:before>
            <q-list>
              <div>
                <q-item clickable @click="route(selectedDomainId)" :class="selectedTab === '' ? 'bg-selected-item' : ''">
                  <q-item-section>
                    <q-item-label lines="1" v-t="'ADMINPANELWEBCLIENT.LABEL_COMMON_SETTINGS_TAB'"></q-item-label>
                  </q-item-section>
                </q-item>
                <q-separator/>
              </div>
              <div v-for="tab in tabs" :key="tab.tabName">
                <q-item clickable @click="route(selectedDomainId, tab.tabName)"
                        :class="selectedTab === tab.tabName ? 'bg-selected-item' : ''">
                  <q-item-section>
                    <q-item-label lines="1">{{ $t(tab.tabTitle) }}</q-item-label>
                  </q-item-section>
                </q-item>
                <q-separator/>
              </div>
              <q-inner-loading style="justify-content: flex-start;" :showing="deleting">
                <q-linear-progress query/>
              </q-inner-loading>
            </q-list>
          </template>
          <template v-slot:after>
            <router-view @no-domain-found="handleNoDomainFound" @domain-created="handleCreateDomain"
                         @cancel-create="route" @delete-domain="askDeleteDomain" :deletingIds="deletingIds"></router-view>
          </template>
        </q-splitter>
        <router-view v-if="!showTabs" @no-domain-found="handleNoDomainFound" @domain-created="handleCreateDomain"
                     @cancel-create="route" @delete-domain="askDeleteDomain" :deletingIds="deletingIds"></router-view>
      </template>
      <ConfirmDialog ref="confirmDialog"/>
    </q-splitter>
  </main-layout>
</template>

<script>
import _ from 'lodash'

import errors from 'src/utils/errors'
import modulesManager from 'src/modules-manager'
import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'

import MainLayout from 'src/layouts/MainLayout'
import ConfirmDialog from 'src/components/ConfirmDialog'
import StandardList from 'src/components/StandardList'

import Add from 'src/assets/icons/Add'
import Trash from 'src/assets/icons/Trash'

export default {
  name: 'Domains',

  components: {
    MainLayout,
    ConfirmDialog,
    StandardList,
    Add,
    Trash
  },

  data() {
    return {
      domains: [],
      selectedDomainId: 0,
      totalCount: 0,

      search: '',
      page: 1,
      limit: 10,

      domainItems: [],
      checkedIds: [],

      justCreatedId: 0,

      deletingIds: [],

      tabs: [],
      selectedTab: '',

      listSplitterWidth: typesUtils.pInt(localStorage.getItem('aurora_admin_domains_splitter-width'), 20),
      tabsSplitterWidth: typesUtils.pInt(localStorage.getItem('aurora_admin_domains_tabs_splitter-width'), 20),
    }
  },

  computed: {
    currentTenantId () {
      return this.$store.getters['tenants/getCurrentTenantId']
    },

    loadingDomains () {
      return this.$store.getters['maildomains/getLoadingForTenant'] === this.currentTenantId || !_.isEmpty(this.deletingIds)
    },

    allTenantDomains () {
      const allDomainLists = this.$store.getters['maildomains/getDomains']
      return typesUtils.pArray(allDomainLists[this.currentTenantId])
    },

    pagesCount () {
      return Math.ceil(this.totalCount / this.limit)
    },

    countLabel () {
      const count = this.checkedIds.length
      return count > 0 ? count : ''
    },

    deleting () {
      return this.deletingIds.indexOf(this.selectedUserId) !== -1
    },

    showTabs () {
      return this.tabs.length > 0 && this.selectedDomainId > 0
    },
  },

  watch: {
    currentTenantId () {
      if (this.$route.path !== '/domains') {
        this.route()
      }
      this.requestDomains()
      this.populate()
    },

    $route (to, from) {
      this.parseRoute()
    },

    allTenantDomains () {
      this.populate()
    },

    domains () {
      this.domainItems = this.domains.map(domain => {
        return {
          id: domain.id,
          title: domain.name,
          rightText: domain.count,
          checked: false,
        }
      })
    },

    listSplitterWidth () {
      localStorage.setItem('aurora_admin_domains_splitter-width', this.listSplitterWidth)
    },

    tabsSplitterWidth (tabsSplitterWidth) {
      localStorage.setItem('aurora_admin_domains_tabs_splitter-width', tabsSplitterWidth)
    },
  },

  mounted () {
    this.requestDomains()
    this.populateTabs()
    this.populate()
    this.parseRoute()
  },

  methods: {
    requestDomains () {
      this.$store.dispatch('maildomains/requestDomains', {
        tenantId: this.currentTenantId
      })
    },

    parseRoute () {
      if (this.$route.path === '/domains/create') {
        this.selectedDomainId = 0
      } else {
        const search = typesUtils.pString(this.$route?.params?.search)
        const page = typesUtils.pPositiveInt(this.$route?.params?.page)
        if (this.search !== search || this.page !== page || this.justCreatedId !== 0) {
          this.search = search
          this.page = page
          this.populate()
        }

        const domainId = typesUtils.pNonNegativeInt(this.$route?.params?.id)
        if (this.selectedDomainId !== domainId) {
          this.selectedDomainId = domainId
        }

        const pathParts = this.$route.path.split('/')
        const lastPart = pathParts.length > 0 ? pathParts[pathParts.length - 1] : ''
        const tab = this.tabs.find(tab => { return tab.tabName === lastPart })
        this.selectedTab = tab ? tab.tabName : ''
      }
    },

    populateTabs () {
      this.tabs = modulesManager.getAdminEntityTabs('getAdminDomainTabs').map(tab => {
        return {
          tabName: tab.tabName,
          tabTitle: tab.tabTitle,
        }
      })
    },

    populate () {
      const search = this.search.toLowerCase()
      const domains = search === ''
        ? this.allTenantDomains
        : this.allTenantDomains.filter(tenant => tenant.name.toLowerCase().indexOf(search) !== -1)
      this.totalCount = domains.length
      const offset = this.limit * (this.page - 1)
      this.domains = domains.slice(offset, offset + this.limit)
    },

    route (domainId = 0, tabName = '') {
      const enteredSearch = this.$refs?.domainList?.enteredSearch || ''
      const searchRoute = enteredSearch !== '' ? `/search/${enteredSearch}` : ''

      let selectedPage = this.$refs?.domainList?.selectedPage || 1
      if (this.search !== enteredSearch) {
        selectedPage = 1
      }
      const pageRoute = selectedPage > 1 ? `/page/${selectedPage}` : ''

      const idRoute = domainId > 0 ? `/id/${domainId}` : ''
      const tabRoute = tabName !== '' ? `/${tabName}` : ''
      const path = '/domains' + searchRoute + pageRoute + idRoute + tabRoute
      if (path !== this.$route.path) {
        this.$router.push(path)
      }
    },

    routeCreateDomain () {
      this.$router.push('/domains/create')
    },

    handleCreateDomain (id) {
      this.justCreatedId = id
      this.route()
      this.requestDomains()
    },

    afterCheck (ids) {
      this.checkedIds = ids
    },

    handleNoDomainFound () {
      this.route()
      this.populate()
    },

    askDeleteDomain (id) {
      this.askDeleteDomains([id])
    },

    askDeleteCheckedDomains () {
      this.askDeleteDomains(this.checkedIds)
    },

    askDeleteDomains (ids) {
      if (_.isFunction(this?.$refs?.confirmDialog?.openDialog)) {
        const domain = ids.length === 1
          ? this.domains.find(domain => {
            return domain.id === ids[0]
          })
          : null
        const title = domain ? domain.name : ''
        this.$refs.confirmDialog.openDialog({
          title,
          message: this.$tc('MAILDOMAINS.CONFIRM_DELETE_MAILDOMAIN_PLURAL', ids.length),
          okHandler: this.deleteDomains.bind(this, ids)
        })
      }
    },

    deleteDomains (ids) {
      this.deletingIds = ids
      webApi.sendRequest({
        moduleName: 'MailDomains',
        methodName: 'DeleteDomains',
        parameters: {
          IdList: ids,
          DeletionConfirmedByAdmin: true
        },
      }).then(result => {
        this.deletingIds = []
        if (result === true) {
          notification.showReport(this.$tc('MAILDOMAINS.REPORT_DELETE_ENTITIES_MAILDOMAIN_PLURAL', ids.length))
          const isSelectedDomainRemoved = ids.indexOf(this.selectedDomainId) !== -1
          const selectedPage = this.$refs?.domainList?.selectedPage || 1
          const shouldChangePage = this.domains.length === ids.length && selectedPage > 1
          if (shouldChangePage && _.isFunction(this.$refs?.domainList?.decreasePage)) {
            this.$refs.domainList.decreasePage()
          } else if (isSelectedDomainRemoved) {
            this.route()
            this.populate()
          } else {
            this.populate()
          }
        } else {
          notification.showError(this.$tc('MAILDOMAINS.ERROR_DELETE_ENTITIES_MAILDOMAIN_PLURAL', ids.length))
        }
        this.requestDomains()
      }, error => {
        this.deletingIds = []
        notification.showError(errors.getTextFromResponse(error, this.$tc('MAILDOMAINS.ERROR_DELETE_ENTITIES_MAILDOMAIN_PLURAL', ids.length)))
        this.requestDomains()
      })
    },
  },
}
</script>

<style lang="scss">
</style>
