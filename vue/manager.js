export default {
  moduleName: 'MailDomains',

  requiredModules: ['MailWebclient'],

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
