<template>
  <div v-if="filterOptions.length > 0">
    <q-select outlined dense class="bg-white domains-select"
              v-model="currentFilter" :options="filterOptions">
      <template v-slot:selected>
        <div class="ellipsis">{{ currentFilter.label }}</div>
      </template>
    </q-select>
  </div>
</template>

<script>
import _ from 'lodash'

import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'

export default {
  name: 'DomainFilterForUser',

  filterRoute: 'domain/:domain',

  data () {
    return {
      filterOptions: [],
      filterValue: null,
      currentFilter: null,
    }
  },

  computed: {
    currentTenantId () {
      return this.$store.getters['tenants/getCurrentTenantId']
    },

    visible () {
      return this.filterOptions.length > 0
    },

    allDomainLists () {
      return this.$store.getters['maildomains/getDomains']
    },

    domains () {
      return typesUtils.pArray(this.allDomainLists[this.currentTenantId])
    }
  },

  watch: {
    $route (to, from) {
      this.fillUpFilterValue()
      this.currentFilter = this.findCurrentFilter()
    },

    filterOptions () {
      this.fillUpFilterValue()
      this.currentFilter = this.findCurrentFilter()
    },

    currentTenantId () {
      this.requestDomains()
    },

    currentFilter (option) {
      this.selectFilter(option.value)
    },

    domains () {
      this.fillUpFilterOptions()
    }
  },

  mounted () {
    this.fillUpFilterOptions()
    this.requestDomains()
  },

  methods: {
    requestDomains () {
      this.$store.dispatch('maildomains/requestDomainsIfNecessary', {
        tenantId: this.currentTenantId
      })
    },

    checkDomains () {
      const domains = this.allDomainLists[this.currentTenantId]
      this.$emit('allow-create-user', { tenantId: this.currentTenantId, allowCreateUser: _.isArray(domains) && domains.length > 0 })
      if (_.isArray(domains) && domains.length === 0) {
        notification.showError(this.$t('MAILDOMAINS.ERROR_ADD_DOMAIN_FIRST'))
      }
    },

    fillUpFilterOptions () {
      const options = this.domains.map(domain => {
        return {
          label: domain.name,
          value: domain.id,
        }
      })
      if (options.length > 0) {
        options.unshift({
          label: this.$t('MAILDOMAINS.LABEL_ALL_DOMAINS'),
          value: -1,
        })
        options.push({
          label: this.$t('MAILDOMAINS.LABEL_NOT_IN_ANY_DOMAIN'),
          value: 0,
        })
      }
      this.filterOptions = options
      this.currentFilter = this.findCurrentFilter()
      this.checkDomains()
    },

    findCurrentFilter () {
      if (this.filterOptions.length) {
        const option = this.filterOptions.find(filter => filter.value === this.filterValue)
        return option || this.filterOptions[0]
      }
      return ''
    },

    fillUpFilterValue () {
      this.filterValue = typesUtils.pInt(this.$route?.params?.domain, -1)
      this.$emit('filter-filled-up', {
        DomainId: this.filterValue
      })
    },

    selectFilter (value) {
      if (value === -1) {
        this.$emit('filter-selected', {
          routeName: 'domain',
        })
      } else {
        this.$emit('filter-selected', {
          routeName: 'domain',
          routeValue: value,
        })
      }
    },
  },
}
</script>

<style scoped>

</style>
