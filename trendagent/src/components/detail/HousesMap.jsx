import './HousesMap.css'
import { getImageUrl } from '../../utils/imageUtils'

const HousesMap = ({ unifiedData, housesData }) => {
  if (!unifiedData && !housesData) {
    return null
  }

  // Получаем координаты из unified данных
  const coordinates = unifiedData?.coordinates || 
                     unifiedData?.location?.coordinates ||
                     unifiedData?.point?.coordinates ||
                     null

  const address = unifiedData?.address || ''

  // Если нет координат, показываем заглушку
  if (!coordinates) {
    return (
      <div className="houses-map-section">
        <h2>Карта</h2>
        <div className="houses-map-content">
          <div className="map-placeholder">
            <p>Карта будет отображаться здесь</p>
            {address && (
              <p className="map-address">Адрес: {address}</p>
            )}
          </div>
        </div>
      </div>
    )
  }

  // Формируем URL для Яндекс.Карт или Google Maps
  const lat = Array.isArray(coordinates) ? coordinates[1] : coordinates.lat || coordinates[latitude] || null
  const lng = Array.isArray(coordinates) ? coordinates[0] : coordinates.lng || coordinates.lon || coordinates.longitude || null

  if (!lat || !lng) {
    return (
      <div className="houses-map-section">
        <h2>Карта</h2>
        <div className="houses-map-content">
          <div className="map-placeholder">
            <p>Координаты не указаны</p>
            {address && (
              <p className="map-address">Адрес: {address}</p>
            )}
          </div>
        </div>
      </div>
    )
  }

  // Используем Яндекс.Карты
  const yandexMapUrl = `https://yandex.ru/maps/?pt=${lng},${lat}&z=15&l=map`

  return (
    <div className="houses-map-section">
      <h2>Карта</h2>
      <div className="houses-map-content">
        <div className="map-container">
          <iframe
            src={`https://yandex.ru/map-widget/v1/?pt=${lng},${lat}&z=15&l=map`}
            width="100%"
            height="500"
            frameBorder="0"
            allowFullScreen
            title="Карта расположения"
            className="map-iframe"
          />
          <div className="map-info">
            {address && (
              <div className="map-address-info">
                <strong>Адрес:</strong> {address}
              </div>
            )}
            <div className="map-coordinates">
              <strong>Координаты:</strong> {lat}, {lng}
            </div>
            <a 
              href={yandexMapUrl} 
              target="_blank" 
              rel="noopener noreferrer"
              className="map-link"
            >
              Открыть в Яндекс.Картах
            </a>
          </div>
        </div>
      </div>
    </div>
  )
}

export default HousesMap
