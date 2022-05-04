import Vue from 'vue'

import _ from 'lodash'

import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'

import DomainModel from '../classes/domain'

export default {
  namespaced: true,

  state: {
    domains: {},
    loadingForTenant: null,
  },

  mutations: {
    setDomains(state, { tenantId, domains }) {
      Vue.set(state.domains, tenantId, domains)
    },

    setLoadingForTenant(state, tenantId) {
      state.loadingForTenant = tenantId
    },
  
    setDomainData ({ getters }, { domain, domainData }) {
      for (const key in domainData) {
        Vue.set(domain.data, key, domainData[key])
      }
    },
  },

  actions: {
    requestDomainsIfNecessary({ state, dispatch }, { tenantId }) {
      if (state.domains[tenantId] === undefined) {
        dispatch('requestDomains', { tenantId })
      }
    },

    requestDomains({ commit }, { tenantId }) {
      const parameters = {
        TenantId: tenantId,
      }
      commit('setLoadingForTenant', tenantId)
      webApi.sendRequest({
        moduleName: 'MailDomains',
        methodName: 'GetDomains',
        parameters,
      }).then(result => {
        if (_.isArray(result?.Items)) {
          const domains = _.map(result.Items, function (data) {
            return new DomainModel(data)
          })
          commit('setDomains', { tenantId, domains })
        } else {
          commit('setDomains', { tenantId, domains: [] })
        }
        commit('setLoadingForTenant', null)
      }, response => {
        notification.showError(errors.getTextFromResponse(response))
        commit('setDomains', { tenantId, domains: [] })
        commit('setLoadingForTenant', null)
      })
    },

    setDomainData ({ getters, commit }, { tenantId, domainId, domainData }) {
      const domain = getters['getDomain'](tenantId, domainId)
      commit('setDomainData', { domain, domainData })
    },
  },

  getters: {
    getDomains (state) {
      return state.domains
    },

    getLoadingForTenant (state) {
      return state.loadingForTenant
    },

    getDomain (state) {
      return function (tenantId, domainId) {
        const tenantDomains = typesUtils.pArray(state.domains[tenantId])
        return tenantDomains.find(domain => domain.id === domainId)
      }
    },
  },
}
