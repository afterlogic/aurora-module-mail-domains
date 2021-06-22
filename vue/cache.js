import _ from 'lodash'

import errors from 'src/utils/errors'
import notification from 'src/utils/notification'
import typesUtils from 'src/utils/types'
import webApi from 'src/utils/web-api'

import DomainModel from 'src/../../../MailDomains/vue/classes/domain'

const allDomains = {}

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
}
