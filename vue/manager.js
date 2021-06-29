import eventBus from 'src/event-bus'

import _ from 'lodash'

export default {
  moduleName: 'MailDomains',

  requiredModules: ['MailWebclient'],

  initSubscriptions (appData) {
    eventBus.$on('MailWebclient::DisableEditDomainsInServer', params => {
      if (_.isObject(params)) {
        params.disableEditDomainsInServer = true
      }
    })
  },

  init (appData) {},

  getPages () {
    return [
      {
        pageName: 'domains',
        beforeUsers: true,
        pageTitle: 'MAILDOMAINS.HEADING_MAILDOMAIN_SETTINGS_TABNAME',
        component () {
          return import('src/../../../MailDomains/vue/pages/Domains')
        },
      }
    ]
  },

  getUserMainDataComponent () {
    return import('src/../../../MailDomains/vue/components/EditUserMainData')
  },
}
