import './ObjectPlots.css'

const ObjectPlots = ({ plotsData, unifiedData }) => {
  if (!plotsData && !unifiedData) {
    return (
      <div className="object-plots card">
        <h2>Участки</h2>
        <p>Информация об участках отсутствует</p>
      </div>
    )
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const getPlotsInfo = () => {
    const data = unifiedData || plotsData || {}
    
    return {
      plotsCount: data.plots_count || data.view_plots_count || 0,
      minPrices: data.min_prices || [],
      areaFrom: data.plot_area_from || data.area_from || null,
      areaTo: data.plot_area_to || data.area_to || null,
      infrastructure: data.infrastructure || [],
      communications: data.communications || [],
      villageName: data.village_name || data.name || null,
      address: data.address || null,
      builder: data.builder || null,
      deadline: data.deadline || null,
      description: data.description || data.about || null,
    }
  }

  const info = getPlotsInfo()

  return (
    <div className="object-plots card">
      <h2>Информация об участках</h2>
      
      <div className="plots-info">
        {/* Основная информация */}
        <div className="info-section">
          <h3>Основная информация</h3>
          <div className="info-grid">
            {info.villageName && (
              <div className="info-item">
                <span className="info-label">Поселок:</span>
                <span className="info-value">{info.villageName}</span>
              </div>
            )}
            {info.address && (
              <div className="info-item">
                <span className="info-label">Адрес:</span>
                <span className="info-value">{info.address}</span>
              </div>
            )}
            {info.builder && (
              <div className="info-item">
                <span className="info-label">Застройщик:</span>
                <span className="info-value">{info.builder.name || info.builder}</span>
              </div>
            )}
            {info.plotsCount > 0 && (
              <div className="info-item">
                <span className="info-label">Количество участков:</span>
                <span className="info-value">{info.plotsCount}</span>
              </div>
            )}
            {info.deadline && (
              <div className="info-item">
                <span className="info-label">Срок сдачи:</span>
                <span className="info-value">{info.deadline}</span>
              </div>
            )}
          </div>
        </div>

        {/* Цены */}
        {info.minPrices.length > 0 && (
          <div className="info-section">
            <h3>Цены</h3>
            <div className="prices-list">
              {info.minPrices.map((price, index) => (
                <div key={index} className="price-item">
                  {price.label && <span className="price-label">{price.label}:</span>}
                  <span className="price-value">
                    {formatPrice(price.value)} {price.unit || '₽'}
                  </span>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Площадь */}
        {(info.areaFrom || info.areaTo) && (
          <div className="info-section">
            <h3>Площадь участков</h3>
            <div className="area-info">
              {info.areaFrom && info.areaTo ? (
                <span>{info.areaFrom} - {info.areaTo} м²</span>
              ) : info.areaFrom ? (
                <span>от {info.areaFrom} м²</span>
              ) : info.areaTo ? (
                <span>до {info.areaTo} м²</span>
              ) : null}
            </div>
          </div>
        )}

        {/* Коммуникации */}
        {info.communications && info.communications.length > 0 && (
          <div className="info-section">
            <h3>Коммуникации</h3>
            <div className="communications-list">
              {Array.isArray(info.communications) ? (
                info.communications.map((comm, index) => (
                  <span key={index} className="communication-tag">
                    {comm.name || comm}
                  </span>
                ))
              ) : (
                <span className="communication-tag">{info.communications}</span>
              )}
            </div>
          </div>
        )}

        {/* Инфраструктура */}
        {info.infrastructure && info.infrastructure.length > 0 && (
          <div className="info-section">
            <h3>Инфраструктура</h3>
            <div className="infrastructure-list">
              {Array.isArray(info.infrastructure) ? (
                info.infrastructure.map((infra, index) => (
                  <span key={index} className="infrastructure-tag">
                    {infra.name || infra}
                  </span>
                ))
              ) : (
                <span className="infrastructure-tag">{info.infrastructure}</span>
              )}
            </div>
          </div>
        )}

        {/* Описание */}
        {info.description && (
          <div className="info-section">
            <h3>Описание</h3>
            <div className="description-text" dangerouslySetInnerHTML={{ __html: info.description }} />
          </div>
        )}
      </div>
    </div>
  )
}

export default ObjectPlots
