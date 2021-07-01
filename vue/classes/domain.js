import typesUtils from 'src/utils/types'

class DomainModel {
  constructor (data) {
    this.tenantId = typesUtils.pInt(data?.TenantId)
    this.mailServerId = typesUtils.pInt(data?.MailServerId)
    this.id = typesUtils.pInt(data?.Id)
    this.name = typesUtils.pString(data?.Name)
    this.count = typesUtils.pInt(data?.Count)
  }
}

export default DomainModel
