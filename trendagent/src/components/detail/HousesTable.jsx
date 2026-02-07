import './HousesTable.css'
import { getImageUrl } from '../../utils/imageUtils'

const HousesTable = ({ housesData, unifiedData }) => {
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
  const houses = housesData?.data || housesData?.apartments?.data || housesData || []
  const hasHouses = Array.isArray(houses) && houses.length > 0

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
                const deadline = formatDate(house.deadline)
                const status = house.status || house.booking_status || 'Доступен'

                return (
                  <tr key={index}>
                    <td className="table-image">
                      {imageUrl ? (
                        <img src={imageUrl} alt={house.name || 'Дом'} />
                      ) : (
                        <div className="image-placeholder">—</div>
                      )}
                    </td>
                    <td className="table-name">{house.name || house.title || 'Без названия'}</td>
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
                      <span className={`status-badge status-${status.toLowerCase().replace(/\s+/g, '-')}`}>
                        {status}
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
