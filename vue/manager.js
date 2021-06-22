export default {
  moduleName: 'MailDomains',

  requiredModules: ['MailWebclient'],

  init (appData) {},

  getUserMainDataComponent () {
    return import('src/../../../MailDomains/vue/components/EditUserMainData')
  },
}
