import './HousesTable.css'
import { getImageUrl } from '../../utils/imageUtils'

const HousesTable = ({ housesData, unifiedData, objectType }) => {
  if (!housesData && !unifiedData) {
    return null
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const formatDate = (date) => {
    if (!date) return null
    try {
      const d = new Date(date)
      if (isNaN(d.getTime())) return null
      return d.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long' })
    } catch (e) {
      return null
    }
  }

  // Получаем данные домов из apartments или houses
  // Для домов данные приходят в apartments.data (коттеджи и таунхаусы с room=[30,40])
  let houses = []
  
  if (housesData) {
    // Проверяем разные возможные структуры данных
    if (Array.isArray(housesData)) {
      houses = housesData
    } else if (housesData.data && Array.isArray(housesData.data)) {
      houses = housesData.data
    } else if (housesData.apartments) {
      if (Array.isArray(housesData.apartments)) {
        houses = housesData.apartments
      } else if (housesData.apartments.data && Array.isArray(housesData.apartments.data)) {
        houses = housesData.apartments.data
      } else if (housesData.apartments.results && Array.isArray(housesData.apartments.results)) {
        houses = housesData.apartments.results
      }
    } else if (housesData.results && Array.isArray(housesData.results)) {
      houses = housesData.results
    }
  }
  
  const hasHouses = houses.length > 0
  
  // Логируем для отладки
  if (objectType === 'houses') {
    console.log('HousesTable: housesData structure', {
      hasHousesData: !!housesData,
      housesDataKeys: housesData ? Object.keys(housesData) : [],
      housesCount: houses.length,
      firstHouse: houses[0] || null,
    })
  }

  if (!hasHouses) {
    return (
      <div className="houses-table-section">
        <h2>Таблица домов</h2>
        <div className="houses-table-content">
          <p>Данные о домах отсутствуют</p>
        </div>
      </div>
    )
  }

  return (
    <div className="houses-table-section">
      <h2>Таблица домов</h2>
      <div className="houses-table-content">
        <div className="houses-table-wrapper">
          <table className="houses-table">
            <thead>
              <tr>
                <th>Изображение</th>
                <th>Название</th>
                <th>Площадь</th>
                <th>Цена</th>
                <th>Срок сдачи</th>
                <th>Статус</th>
              </tr>
            </thead>
            <tbody>
              {houses.map((house, index) => {
                const imageUrl = house.image?.url || 
                                (house.images && house.images.length > 0 ? getImageUrl(house.images[0]) : null) ||
                                house.image
                const area = house.area || house.area_from || house.area_to || null
                const price = house.price || 
                             (house.min_prices && house.min_prices.length > 0 ? (house.min_prices[0].price || house.min_prices[0].value) : null) ||
                             house.min_price ||
                             house.price_from
                // Обработка deadline
                let deadline = null
                if (house.deadline) {
                  if (Array.isArray(house.deadline) && house.deadline.length > 0) {
                    deadline = formatDate(house.deadline[0].deadline || house.deadline[0])
                  } else {
                    deadline = formatDate(house.deadline)
                  }
                }
                
                // Обработка статуса - может быть строкой, объектом или null
                let status = 'Доступен'
                let statusText = 'Доступен'
                if (house.status) {
                  if (typeof house.status === 'string') {
                    status = house.status
                    statusText = house.status
                  } else if (typeof house.status === 'object' && house.status.name) {
                    status = house.status.name
                    statusText = house.status.name
                  } else if (typeof house.status === 'object' && house.status.value) {
                    status = house.status.value
                    statusText = house.status.value
                  }
                } else if (house.booking_status) {
                  if (typeof house.booking_status === 'string') {
                    status = house.booking_status
                    statusText = house.booking_status
                  } else if (typeof house.booking_status === 'object' && house.booking_status.name) {
                    status = house.booking_status.name
                    statusText = house.booking_status.name
                  }
                } else if (house.is_booked) {
                  status = 'Забронирован'
                  statusText = 'Забронирован'
                }
                
                // Безопасное преобразование статуса для CSS класса
                const statusClass = typeof status === 'string' 
                  ? status.toLowerCase().replace(/\s+/g, '-')
                  : 'available'

                return (
                  <tr key={index}>
                    <td className="table-image">
                      {imageUrl ? (
                        <img src={imageUrl} alt={name || 'Дом'} />
                      ) : (
                        <div className="image-placeholder">—</div>
                      )}
                    </td>
                    <td className="table-name">{name}</td>
                    <td className="table-area">
                      {area ? (
                        typeof area === 'object' && area.from && area.to ? (
                          `${area.from} - ${area.to} м²`
                        ) : (
                          `${area} м²`
                        )
                      ) : (
                        '—'
                      )}
                    </td>
                    <td className="table-price">
                      {price && price > 0 ? (
                        <span className="price-value">от {formatPrice(price)}</span>
                      ) : (
                        '—'
                      )}
                    </td>
                    <td className="table-deadline">{deadline || '—'}</td>
                    <td className="table-status">
                      <span className={`status-badge status-${statusClass}`}>
                        {statusText}
                      </span>
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  )
}

export default HousesTable
