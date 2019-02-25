'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	
	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
	
	ModulesManager = require('%PathToCoreWebclientModule%/js/ModulesManager.js'),
	Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),
	
	Cache = require('modules/%ModuleName%/js/Cache.js')
;

/**
 * @constructor
 */
function CEditMailDomainView()
{
	this.sHeading = TextUtils.i18n('%MODULENAME%/HEADING_CREATE_MAILDOMAIN');
	this.id = ko.observable(0);
	this.domain = ko.observable('');
	this.selectedMailServer = ko.observable(0);
	this.count = ko.observable(0);
	
	this.mailServers = ko.computed(function () {
		return _.filter(Cache.mailServers(), function (oMailServer) {
			return oMailServer.TenantId === Cache.selectedTenantId() || oMailServer.TenantId === 0;
		});
	}, this);
	this.mailServerName = ko.computed(function () {
		var oSelectedServer = _.find(this.mailServers() , function (oServer) {
			return oServer.EntityId === this.selectedMailServer();
		}.bind(this));
		return oSelectedServer ? oSelectedServer.Name : '';
	}, this);
}

CEditMailDomainView.prototype.ViewTemplate = '%ModuleName%_EditMailDomainView';

CEditMailDomainView.prototype.getCurrentValues = function ()
{
	return [
		this.id(),
		this.domain(),
		this.selectedMailServer()
	];
};

CEditMailDomainView.prototype.clearFields = function ()
{
	this.id(0);
	this.domain('');
	this.selectedMailServer(0);
};

CEditMailDomainView.prototype.parse = function (iEntityId, oResult)
{
	if (oResult)
	{
		this.id(iEntityId);
		this.domain(oResult.Name);
		this.selectedMailServer(oResult.MailServerId);
		this.count(oResult.Count);
	}
	else
	{
		this.clearFields();
	}
};

CEditMailDomainView.prototype.isValidSaveData = function ()
{
	var bValid = $.trim(this.domain()) !== '';
	if (!bValid)
	{
		Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_MAILDOMAIN_NAME_EMPTY'));
	}
	return bValid;
};

CEditMailDomainView.prototype.getParametersForSave = function ()
{
	return {
		DomainName: this.domain(),
		MailServerId: this.selectedMailServer()
	};
};

CEditMailDomainView.prototype.showUsers = function ()
{
	ModulesManager.run('AdminPanelWebclient', 'showEntities', ['User', {'Domain': this.id()}]);
};

module.exports = new CEditMailDomainView();
