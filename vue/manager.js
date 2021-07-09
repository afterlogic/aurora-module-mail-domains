import eventBus from 'src/event-bus'

import _ from 'lodash'

const _disableEditDomainsInServer = params => {
  if (_.isObject(params)) {
    params.disableEditDomainsInServer = true
  }
}

export default {
  moduleName: 'MailDomains',

  requiredModules: ['MailWebclient'],

  initSubscriptions (appData) {
    eventBus.$off('MailWebclient::DisableEditDomainsInServer', _disableEditDomainsInServer)
    eventBus.$on('MailWebclient::DisableEditDomainsInServer', _disableEditDomainsInServer)
  },

  init (appData) {},

  getPages () {
    return [
      {
        pageName: 'domains',
        beforeUsers: true,
        pageTitle: 'MAILDOMAINS.HEADING_MAILDOMAIN_SETTINGS_TABNAME',
        component () {
          return import('./pages/Domains')
        },
      }
    ]
  },

  getUserMainDataComponent () {
    return import('./components/EditUserMainData')
  },
}
