<template>
  <q-btn-dropdown no-icon-animation cover auto-close stretch flat dense :ripple="false" :label="selectedFilterText"
                  class="q-px-none text-weight-regular no-hover" v-if="visible">
    <q-list class="non-selectable" v-for="option in filterOptions" :key="option.value">
      <q-item clickable @click="selectFilter(option.value)">
        <q-item-section>{{option.text}}</q-item-section>
      </q-item>
    </q-list>
  </q-btn-dropdown>
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
    }
  },

  computed: {
    currentTenantId() {
      return this.$store.getters['tenants/getCurrentTenantId']
    },

    visible () {
      return this.filterOptions.length > 0
    },

    selectedFilterText () {
      const option = this.filterOptions.find(filter => filter.value === this.filterValue)
      return option ? option.text : ''
    },
  },

  watch: {
    $route (to, from) {
      this.fillUpFilterValue()
    },

    filterOptions () {
      this.fillUpFilterValue()
    },
  },

  mounted () {
    cache.getDomains(this.currentTenantId).then(({ domains, totalCount, tenantId }) => {
      if (tenantId === this.currentTenantId) {
        const options = domains.map(domain => {
          return {
            text: domain.name,
            value: domain.id,
          }
        })
        if (options.length > 0) {
          options.unshift({
            text: this.$t('MAILDOMAINS.LABEL_ALL_DOMAINS'),
            value: -1,
          })
          options.push({
            text: this.$t('MAILDOMAINS.LABEL_NOT_IN_ANY_DOMAIN'),
            value: 0,
          })
        }
        this.filterOptions = options
      }
    })
  },

  methods: {
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
