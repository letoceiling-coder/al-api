import './FlatPassport.css'

const FlatPassport = ({ apartment, block }) => {
  const formatPrice = (price) => {
    if (!price || price === 0) return 'Под запрос'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const formatPricePerM2 = (price, area) => {
    if (!price || !area || area === 0) return 'Под запрос'
    const perM2 = Math.round(price / area)
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(perM2)
  }

  const getStatus = (apt) => {
    if (!apt) return 'Свободна'
    if (typeof apt.status === 'string') {
      return apt.status
    }
    if (apt.status?.name) {
      return apt.status.name
    }
    if (apt.booking_status) {
      return apt.booking_status
    }
    if (apt.is_booked) {
      return 'Забронирована'
    }
    return 'Свободна'
  }

  const number = apartment?.number || apartment?.apartment_number || '—'
  const floor = apartment?.floor || '—'
  const totalFloors = apartment?.total_floors || block?.floors || '—'
  const section = apartment?.section_name || apartment?.section || '—'
  const building = apartment?.building_name || apartment?.building || apartment?.corpus || '—'
  const area = apartment?.privArea || apartment?.area || apartment?.area_total || null
  const calculatedArea = apartment?.calculated_area || apartment?.area_calculated || null
  const kitchenArea = apartment?.kitchenArea || apartment?.kitchen_area || null
  const livingArea = apartment?.livingArea || apartment?.living_area || null
  const balconyType = apartment?.balcony_type || apartment?.balcony || '—'
  const finishing = apartment?.finishing_name || apartment?.finishing || 'Без отделки'
  const windows = apartment?.windows || apartment?.window_type || '—'
  const view = apartment?.view_image || apartment?.view || '—'
  const viewType = apartment?.view_type || apartment?.view_category || '—'
  const basePrice = apartment?.base_price || null
  const fullPrice = apartment?.price || apartment?.full_price || null
  const pricePerM2Full = area && fullPrice ? formatPricePerM2(fullPrice, area) : 'Под запрос'
  const pricePerM2Base = area && basePrice ? formatPricePerM2(basePrice, area) : 'Под запрос'
  const status = getStatus(apartment)
  const rooms = apartment?.rooms || apartment?.room || null

  return (
    <div className="apartment-passport">
      <div className="price-passport__item row flex-nowrap">
        <div className="price-passport__item-col col">Цена, 100% оплата</div>
        <div className="price-passport__item-col col-auto">{fullPrice ? formatPrice(fullPrice) : 'Под запрос'}</div>
      </div>
      
      <div className="apartment-passport__row row">
        <div className="col-auto">Цена за м² при 100% оплате</div>
        <div className="col-auto"><span>{pricePerM2Full}</span></div>
      </div>
      
      <div className="apartment-passport__row row">
        <div className="col-auto">Базовая цена</div>
        <div className="col-auto"><span>{basePrice ? formatPrice(basePrice) : 'Под запрос'}</span></div>
      </div>
      
      <div className="apartment-passport__row row">
        <div className="col-auto">Цена за м² при базовой цене</div>
        <div className="col-auto"><span>{pricePerM2Base}</span></div>
      </div>
      
      <div className="apartment-passport__row row">
        <div className="col-auto">Квартира</div>
        <div className="col-auto col-auto--tags">
          <span>
            <div className="apartment-passport__tags">
              <div className="shell-element shell-element_sm shell-element_square-sm shell-element_border badge apartment-passport__tags-status" style={{ backgroundColor: 'rgb(255, 249, 224)', borderColor: 'rgb(255, 249, 224)', borderRadius: '15px', color: 'rgb(51, 51, 51)' }}>
                <span className="apartment-passport__tag-item">{status}</span>
              </div>
            </div>
          </span>
        </div>
      </div>
      
      {area && (
        <div className="apartment-passport__row row">
          <div className="col-auto">S общая</div>
          <div className="col-auto"><span>{area} м²</span></div>
        </div>
      )}
      
      {calculatedArea && (
        <div className="apartment-passport__row row">
          <div className="col-auto">S приведенная (для расчета цены)</div>
          <div className="col-auto"><span>{calculatedArea} м²</span></div>
        </div>
      )}
      
      {kitchenArea && (
        <div className="apartment-passport__row row">
          <div className="col-auto">S кухни</div>
          <div className="col-auto"><span>{kitchenArea} м²</span></div>
        </div>
      )}
      
      {balconyType !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Тип балкона</div>
          <div className="col-auto"><span>{balconyType}</span></div>
        </div>
      )}
      
      <div className="apartment-passport__row row">
        <div className="col-auto">Номер квартиры</div>
        <div className="col-auto"><span>{number}</span></div>
      </div>
      
      <div className="apartment-passport__row row">
        <div className="col-auto">Этаж / Этажность</div>
        <div className="col-auto"><span>{floor} / {totalFloors}</span></div>
      </div>
      
      <div className="apartment-passport__row row">
        <div className="col-auto">Отделка</div>
        <div className="col-auto"><span>{finishing}</span></div>
      </div>
      
      {windows !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Окна</div>
          <div className="col-auto"><span>{windows}</span></div>
        </div>
      )}
      
      {view !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Вид из окна</div>
          <div className="col-auto"><span>{view}</span></div>
        </div>
      )}
      
      {viewType !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Видовая квартира</div>
          <div className="col-auto"><span>{viewType}</span></div>
        </div>
      )}
    </div>
  )
}

export default FlatPassport
