import './VillagePassport.css'

const VillagePassport = ({ unifiedData }) => {
  if (!unifiedData) {
    return null
  }

  const getPassportData = () => {
    // Извлекаем данные из unified или из других источников
    const data = unifiedData || {}
    
    return {
      builder: data.builder?.name || data.builder || null,
      villageClass: data.village_class || data.class || null,
      unifiedStyle: data.unified_style || data.architectural_style || null,
      landPurpose: data.land_purpose || data.purpose || null,
      waterSupply: data.water_supply || null,
      sewerage: data.sewerage || null,
      gasSupply: data.gas_supply || null,
      electricity: data.electricity || null,
      powerKw: data.power_kw || data.power || null,
      managementCompany: data.management_company || null,
      registration: data.registration || data.permanent_registration || null,
      fiberInternet: data.fiber_internet || null,
      road: data.road || data.road_type || null,
      payment: data.payment || data.payment_types || null,
      contract: data.contract || data.contract_type || null,
      escrow: data.escrow || null,
    }
  }

  const passport = getPassportData()

  const renderValue = (value) => {
    if (value === null || value === undefined) return null
    if (typeof value === 'boolean') return value ? 'Да' : 'Нет'
    if (Array.isArray(value)) return value.join(', ')
    return String(value)
  }

  const passportRows = [
    { label: 'Застройщик', value: passport.builder },
    { label: 'Класс посёлка', value: passport.villageClass },
    { label: 'Единый архитектурный стиль', value: passport.unifiedStyle },
    { label: 'Назначение земли', value: passport.landPurpose },
    { label: 'Водоснабжение', value: passport.waterSupply },
    { label: 'Канализация', value: passport.sewerage },
    { label: 'Газоснабжение', value: passport.gasSupply },
    { label: 'Электричество', value: passport.electricity },
    { label: 'Количество кВт', value: passport.powerKw },
    { label: 'Собственная управляющая компания', value: passport.managementCompany },
    { label: 'Возможность постоянной регистрации', value: passport.registration },
    { label: 'Оптоволоконный интернет', value: passport.fiberInternet },
    { label: 'Дорога к посёлку', value: passport.road },
    { label: 'Оплата', value: passport.payment },
    { label: 'Договор', value: passport.contract },
    { label: 'Эскроу', value: passport.escrow },
  ].filter(row => row.value !== null && row.value !== undefined)

  if (passportRows.length === 0) {
    return null
  }

  return (
    <div className="village-passport">
      <div className="passport-container">
        {passportRows.map((row, index) => (
          <div key={index} className="passport-row">
            <div className="passport-row-label">{row.label}</div>
            <div className="passport-row-value">{renderValue(row.value)}</div>
          </div>
        ))}
      </div>
      <button className="btn btn-primary passport-contacts-btn">
        Контакты
      </button>
    </div>
  )
}

export default VillagePassport
