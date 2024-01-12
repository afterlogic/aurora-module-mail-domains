import _ from 'lodash'

import enums from 'src/enums'
import eventBus from 'src/event-bus'
import routesManager from 'src/router/routes-manager'

import Empty from 'src/components/Empty'
import EditDomain from './components/EditDomain'
import DomainFilterForUsers from './components/DomainFilterForUsers'

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

  getRoutes () {
    const UserRoles = enums.getUserRoles()
    return [
      {
        name: 'domains',
        path: '/domains',
        component: () => import('./pages/Domains'),
        children: [
          { path: 'id/:id', component: EditDomain },
          { path: 'create', component: EditDomain },
          { path: 'search/:search', component: Empty },
          { path: 'search/:search/id/:id', component: EditDomain },
          { path: 'page/:page', component: Empty },
          { path: 'page/:page/id/:id', component: EditDomain },
          { path: 'search/:search/page/:page', component: Empty },
          { path: 'search/:search/page/:page/id/:id', component: EditDomain },
        ].concat(routesManager.getRouteChildren('Domain')),
        pageUserRoles: [UserRoles.SuperAdmin],
        pageTitle: 'MAILDOMAINS.HEADING_MAILDOMAIN_SETTINGS_TABNAME',
      }
    ]
  },

  getUserMainDataComponent () {
    return import('./components/EditUserMainData')
  },

  getFiltersForUsers () {
    return [
      DomainFilterForUsers
    ]
  },
}
