import Vue from 'vue'

import typesUtils from 'src/utils/types'
import _ from 'lodash'
import webApi from 'src/utils/web-api'

export default {
    namespaced: true,
    state: {
        domains: [],
    },
    mutations: {
        setDomains (state, { tenantId, hash }) {
            Vue.set(state.domains, tenantId, hash)
        },
    },
    actions: {
        requestDomains({ state, commit }, { tenantId }) {
            const parameters = {
                TenantId: tenantId,
            }
            webApi.sendRequest({
                moduleName: 'MailDomains',
                methodName: 'GetDomains',
                parameters,
            }).then(result => {
                if (_.isArray(result?.Items)) {
                    commit('setDomains', { tenantId, hash: result.Items })
                }
            })
        },
    },
    getters: {
        getDomains (state) {
            return state.domains
        },
    },
}
