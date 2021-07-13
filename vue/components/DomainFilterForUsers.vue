<template>
  <div>
    <q-select style="width: 180px" outlined dense class="bg-white"
              v-model="currentFilter" :options="filterOptions"/>
  </div>
</template>

<script>
import typesUtils from 'src/utils/types'

import cache from '../cache'

export default {
  name: 'DomainFilterForUser',
  filterRoute: 'domain/:domain',

  props: {
  },

  data () {
    return {
      filterOptions: [],
      filterValue: null,
      currentFilter: ''
    }
  },

  computed: {
    currentTenantId() {
      return this.$store.getters['tenants/getCurrentTenantId']
    },

    visible () {
      return this.filterOptions.length > 0
    },
  },

  watch: {
    $route (to, from) {
      this.fillUpFilterValue()
      this.currentFilter = this.selectedFilterText()
    },

    filterOptions () {
      this.fillUpFilterValue()
    },
    currentFilter (option) {
      this.selectFilter(option.value)
    }
  },

  mounted () {
    cache.getDomains(this.currentTenantId).then(({ domains, totalCount, tenantId }) => {
      if (tenantId === this.currentTenantId) {
        const options = domains.map(domain => {
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
        this.currentFilter = this.selectedFilterText()
      }
    })
  },

  methods: {
    selectedFilterText () {
      const option = this.filterOptions.find(filter => filter.value === this.filterValue)
      return option ? option : this.filterOptions[0]
    },
    fillUpFilterValue () {
      this.filterValue = typesUtils.pInt(this.$route?.params?.domain, -1)
      this.$emit('filter-filled-up', {
        DomainId: this.filterValue
      })
    },

    selectFilter (value) {
      this.$emit('filter-selected', {
        routeName: 'domain',
        routeValue: value,
      })
    },
  },
}
</script>

<style scoped>

</style>
