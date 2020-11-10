'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),
	
	Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
	App = require('%PathToCoreWebclientModule%/js/App.js'),
	ModulesManager = require('%PathToCoreWebclientModule%/js/ModulesManager.js'),
	Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),
	
	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function CCache()
{
	this.selectedTenantId = ModulesManager.run('AdminPanelWebclient', 'getKoSelectedTenantId');
	this.domainsByTenants = ko.observable({});
	this.mailServers = ko.observable({});
	if (_.isFunction(this.selectedTenantId))
	{
		this.selectedTenantId.subscribe(function () {
			if (typeof this.domainsByTenants()[this.selectedTenantId()] === 'undefined')
			{
				Ajax.send(Settings.ServerModuleName, 'GetDomains', {TenantId: this.selectedTenantId()});
			}
			this.init();
		}, this);
	}
	this.domains = ko.computed(function () {
		var aDomains = _.isFunction(this.selectedTenantId) ? this.domainsByTenants()[this.selectedTenantId()] : [];
		return _.isArray(aDomains) ? aDomains : [];
	}, this);
	
	App.subscribeEvent('AdminPanelWebclient::ConstructView::after', function (oParams) {
		if (oParams.Name === 'CSettingsView' && Types.isPositiveNumber(oParams.View.selectedTenant().Id))
		{
			Ajax.send(Settings.ServerModuleName, 'GetDomains', { 'TenantId': oParams.View.selectedTenant().Id });
		}
	}.bind(this));
	App.subscribeEvent('ReceiveAjaxResponse::after', this.onAjaxResponse.bind(this));
	App.subscribeEvent('SendAjaxRequest::before', this.onAjaxSend.bind(this));
}

CCache.prototype.init = function ()
{
	Ajax.send('Mail', 'GetServers', {TenantId: this.selectedTenantId()}, function (oResponse, oRequest) {
		var aItems = oResponse && oResponse.Result && oResponse.Result.Items;
		if (_.isArray(aItems))
		{
			this.mailServers(_.map(aItems, function (oServerData) {
				return {
					Name: oServerData.Name,
					EntityId: oServerData.EntityId,
					TenantId: oServerData.TenantId
				};
			}));
		}
	}, this);
};

/**
 * Only Cache object knows if domains are empty or not received yet.
 * So error will be shown as soon as domains will be received from server if they are empty.
 * @returns Boolean
 */
CCache.prototype.showErrorIfDomainsEmpty = function ()
{
	var
		bDomainsEmptyOrUndefined = true,
		fShowErrorIfDomainsEmpty = function () {
			if (this.domains().length === 0)
			{
				Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_ADD_DOMAIN_FIRST'));
			}
			else
			{
				bDomainsEmptyOrUndefined = false;
			}
		}.bind(this)
	;
	
	if (_.isFunction(this.selectedTenantId))
	{
		if (typeof this.domainsByTenants()[this.selectedTenantId()] === 'undefined')
		{
			var fSubscription = this.domainsByTenants.subscribe(function () {
				fShowErrorIfDomainsEmpty();
				fSubscription.dispose();
				fSubscription = undefined;
			});
		}
		else
		{
			fShowErrorIfDomainsEmpty();
		}
	}
	
	return bDomainsEmptyOrUndefined;
};

CCache.prototype.getDomain = function (iId)
{
	return _.find(this.domains(), function (oDomain) {
		return oDomain.Id === iId;
	});
};

CCache.prototype.onAjaxSend = function (oParams)
{
	if (oParams.Module === Settings.ServerModuleName && oParams.Method === 'GetDomain')
	{
		var oDomain = this.getDomain(oParams.Parameters.Id);
		if (oDomain)
		{
			if (_.isFunction(oParams.ResponseHandler))
			{
				oParams.ResponseHandler.apply(oParams.Context, [{
					'Module': oParams.Module,
					'Method': oParams.Method,
					'Result': oDomain
				}, {
					'Module': oParams.Module,
					'Method': oParams.Method,
					'Parameters': oParams.Parameters
				}]);
				oParams.Continue = false;
			}
		}
	}
};

CCache.prototype.onAjaxResponse = function (oParams)
{
	var
		sModule = oParams.Response.Module,
		sMethod = oParams.Response.Method
	;
	
	if (sModule === Settings.ServerModuleName && sMethod === 'GetDomains')
	{
		var
			sSearch = Types.pString(oParams.Request.Parameters.Search),
			iOffset = Types.pInt(oParams.Request.Parameters.Offset)
		;
		if (sSearch === '' && iOffset === 0)
		{
			var
				iTenantId = oParams.Request.Parameters.TenantId,
				aDomains = oParams.Response.Result && _.isArray(oParams.Response.Result.Items) ? oParams.Response.Result.Items : []
			;

			_.each(aDomains, function (oDomain) {
				oDomain.Id = Types.pInt(oDomain.Id);
			});

			this.domainsByTenants()[iTenantId] = aDomains;
			this.domainsByTenants.valueHasMutated();
		}
	}
	
	if (sModule === 'Mail' && (sMethod === 'CreateServer' || sMethod === 'UpdateServer' || sMethod === 'DeleteServer'))
	{
		this.init();
	}
};

module.exports = new CCache();
