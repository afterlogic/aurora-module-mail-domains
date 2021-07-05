import _ from 'lodash'

import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'

import DomainModel from './classes/domain'

const allDomains = {}
let currentDomains = []

export default {
  getDomains (tenantId) {
    return new Promise((resolve, reject) => {
      if (_.isObject(allDomains[tenantId])) {
        resolve({ domains: allDomains[tenantId].items, totalCount: allDomains[tenantId].totalCount, tenantId })
      } else {
        webApi.sendRequest({
          moduleName: 'MailDomains',
          methodName: 'GetDomains',
          parameters: {
            TenantId: tenantId,
          },
        }).then(result => {
          if (_.isArray(result?.Items)) {
            const domains = _.map(result.Items, function (data) {
              return new DomainModel(data)
            })
            const totalCount = typesUtils.pInt(result.Count)
            allDomains[tenantId] = {
              items: domains,
              totalCount,
            }
            resolve({ domains, totalCount, tenantId })
          } else {
            resolve({ domains: [], totalCount: 0, tenantId })
          }
        }, response => {
          notification.showError(errors.getTextFromResponse(response))
          resolve({ domains: [], totalCount: 0, tenantId })
        })
      }
    })
  },

  getPagedDomains (tenantId, search, page, limit) {
    return new Promise((resolve, reject) => {
      webApi.sendRequest({
        moduleName: 'MailDomains',
        methodName: 'GetDomains',
        parameters: {
          TenantId: tenantId,
          Search: search,
          Offset: limit * (page - 1),
          Limit: limit,
        },
      }).then(result => {
        if (_.isArray(result?.Items)) {
          const domains = _.map(result.Items, function (data) {
            return new DomainModel(data)
          })
          const totalCount = typesUtils.pInt(result.Count)
          currentDomains = domains
          resolve({ domains, totalCount, tenantId, search, page, limit })
        } else {
          resolve({ domains: [], totalCount: 0, tenantId, search, page, limit })
        }
      }, response => {
        notification.showError(errors.getTextFromResponse(response))
        resolve({ domains: [], totalCount: 0, tenantId, search, page, limit })
      })
    })
  },

  getDomain (tenantId, domainId) {
    return new Promise((resolve, reject) => {
      const domain = currentDomains.find(domain => {
        return domain.tenantId === tenantId && domain.id === domainId
      })
      resolve({ domain, domainId })
    })
  },
}
