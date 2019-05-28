'use strict';

module.exports = function (oAppData) {
	var
		_ = require('underscore'),
		
		App = require('%PathToCoreWebclientModule%/js/App.js'),
				
		TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
		
		ModulesManager = require('%PathToCoreWebclientModule%/js/ModulesManager.js'),

		Cache = require('modules/%ModuleName%/js/Cache.js'),
		Settings = require('modules/%ModuleName%/js/Settings.js')
	;
	
	Settings.init(oAppData);
	
	if (ModulesManager.isModuleAvailable(Settings.ServerModuleName))
	{
		
		function ChangeAdminPanelUserEntityData()
		{
			ModulesManager.run('AdminPanelWebclient', 'changeAdminPanelEntityData', [{
				Type: 'User',
				EditView: require('modules/%ModuleName%/js/views/EditUserView.js'),
				Filters: [
					{
						sEntity: 'Domain',
						sField: 'DomainId',
						mList: function () {
							return _.map(Cache.domains(), function (oDomain) {
								return {
									text: oDomain.Name,
									value: oDomain.Id
								};
							});
						},
						sAllText: TextUtils.i18n('%MODULENAME%/LABEL_ALL_DOMAINS'),
						sNotInAnyText: TextUtils.i18n('%MODULENAME%/LABEL_NOT_IN_ANY_DOMAIN')
					}
				]
			}]);
		}
		
		if (App.getUserRole() === Enums.UserRole.SuperAdmin)
		{
			return {
				/**
				 * Registers admin settings tabs before application start.
				 * 
				 * @param {Object} ModulesManager
				 */
				start: function (ModulesManager)
				{
					ModulesManager.run('MailWebclient', 'disableEditDomainsInServer');
					Cache.init();
					ModulesManager.run('AdminPanelWebclient', 'registerAdminPanelEntityType', [{
						Type: 'Domain',
						ScreenHash: 'domain',
						LinkTextKey: '%MODULENAME%/HEADING_MAILDOMAIN_SETTINGS_TABNAME',
						EditView: require('modules/%ModuleName%/js/views/EditMailDomainView.js'),

						ServerModuleName: Settings.ServerModuleName,
						GetListRequest: 'GetDomains',
						GetRequest: 'GetDomain',
						CreateRequest: 'CreateDomain',
						DeleteRequest: 'DeleteDomains',

						NoEntitiesFoundText: TextUtils.i18n('%MODULENAME%/INFO_NO_ENTITIES_FOUND_MAILDOMAIN'),
						ActionCreateText: TextUtils.i18n('%MODULENAME%/ACTION_ADD_ENTITY_MAILDOMAIN'),
						ReportSuccessCreateText: TextUtils.i18n('%MODULENAME%/REPORT_ADD_ENTITY_MAILDOMAIN'),
						ErrorCreateText: TextUtils.i18n('%MODULENAME%/ERROR_ADD_ENTITY_MAILDOMAIN'),
						CommonSettingsHeadingText: TextUtils.i18n('%MODULENAME%/HEADING_EDIT_MAILDOMAIN'),
						ActionDeleteText: TextUtils.i18n('%MODULENAME%/ACTION_DELETE_MAILDOMAIN'),
						ConfirmDeleteLangConst: '%MODULENAME%/CONFIRM_DELETE_MAILDOMAIN_PLURAL',
						ReportSuccessDeleteLangConst: '%MODULENAME%/REPORT_DELETE_ENTITIES_MAILDOMAIN_PLURAL',
						ErrorDeleteLangConst: '%MODULENAME%/ERROR_DELETE_ENTITIES_MAILDOMAIN_PLURAL'
					}]);
					ChangeAdminPanelUserEntityData();
				},
				getMailDomainsCache: function () {
					return Cache;
				}
			};
		}
		
		if (App.getUserRole() === Enums.UserRole.TenantAdmin)
		{
			return {
				/**
				 * Registers admin settings tabs before application start.
				 * 
				 * @param {Object} ModulesManager
				 */
				start: function (ModulesManager)
				{
					Cache.init();
					ChangeAdminPanelUserEntityData();
				},
				getMailDomainsCache: function () {
					return Cache;
				}
			};
		}
	}
	
	return null;
};
