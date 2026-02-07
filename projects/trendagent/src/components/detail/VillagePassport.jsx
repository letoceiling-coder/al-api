import './VillagePassport.css'

const VillagePassport = ({ unifiedData }) => {
  if (!unifiedData) {
    return null
  }

  const getPassportData = () => {
    // Извлекаем данные из unified или из других источников
    const data = unifiedData || {}
    
    // Проверяем, есть ли вложенный объект passport
    // passport может быть массивом объектов {name, value} или объектом с полями
    let passportObj = {}
    
    if (Array.isArray(data.passport)) {
      // Если passport - массив объектов {name, value}, преобразуем в объект
      data.passport.forEach(item => {
        if (item && item.name && item.value !== undefined) {
          // Преобразуем name в ключ (например, "Застройщик" -> "builder")
          const key = item.name.toLowerCase()
            .replace(/\s+/g, '_')
            .replace(/[^a-z0-9_]/g, '')
          passportObj[key] = item.value
        }
      })
    } else if (data.passport && typeof data.passport === 'object') {
      passportObj = data.passport
    }
    
    return {
      builder: data.builder?.name || data.builder || passportObj.builder?.name || passportObj.builder || null,
      villageClass: data.village_class || data.class || passportObj.village_class || passportObj.class || null,
      unifiedStyle: data.unified_style || data.architectural_style || passportObj.unified_style || passportObj.architectural_style || null,
      landPurpose: data.land_purpose || data.purpose || passportObj.land_purpose || passportObj.purpose || null,
      waterSupply: data.water_supply || passportObj.water_supply || null,
      sewerage: data.sewerage || passportObj.sewerage || null,
      gasSupply: data.gas_supply || passportObj.gas_supply || null,
      electricity: data.electricity || passportObj.electricity || null,
      powerKw: data.power_kw || data.power || passportObj.power_kw || passportObj.power || null,
      managementCompany: data.management_company || passportObj.management_company || null,
      registration: data.registration || data.permanent_registration || passportObj.registration || passportObj.permanent_registration || null,
      fiberInternet: data.fiber_internet || passportObj.fiber_internet || null,
      road: data.road || data.road_type || passportObj.road || passportObj.road_type || null,
      payment: data.payment || data.payment_types || passportObj.payment || passportObj.payment_types || null,
      contract: data.contract || data.contract_type || passportObj.contract || passportObj.contract_type || null,
      escrow: data.escrow || passportObj.escrow || null,
    }
  }

  const passport = getPassportData()

  const renderValue = (value) => {
    if (value === null || value === undefined) return null
    if (typeof value === 'boolean') return value ? 'Да' : 'Нет'
    if (typeof value === 'object') {
      // Если это объект, пытаемся извлечь строковое представление
      if (Array.isArray(value)) return value.join(', ')
      if (value.name) return String(value.name)
      if (value.value !== undefined) return String(value.value)
      return JSON.stringify(value)
    }
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
