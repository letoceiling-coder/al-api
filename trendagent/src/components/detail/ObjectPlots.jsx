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
    <div className="village-plots-section">
      <div className="container">
        <div className="toggled-title">
          <svg className="svg-icon toggled-title__toggler toggled-title__toggler_open" height="20" width="20" viewBox="0 0 20 20" fill="none">
            <path fillRule="evenodd" clipRule="evenodd" d="M10.0001 12.75C10.199 12.75 10.3898 12.671 10.5305 12.5303L14.0305 9.03033C14.3233 8.73744 14.3233 8.26256 14.0305 7.96967C13.7376 7.67678 13.2627 7.67678 12.9698 7.96967L10.0001 10.9393L7.03045 7.96967C6.73756 7.67678 6.26269 7.67678 5.96979 7.96967C5.6769 8.26256 5.6769 8.73744 5.96979 9.03033L9.46979 12.5303C9.61044 12.671 9.80121 12.75 10.0001 12.75Z" fill="#4C4C4C"/>
          </svg>
          <h2 className="toggled-title__title">Участки</h2>
        </div>
      </div>
      
      <div className="village-plots-section__filter village-plots-section__filter_container">
        <div className="village-plots-section__filter village-plots-section__filter_field">
          <div className="container">
            <div className="villagePlots-filter villagePlots-filter_object">
              <div>
                <div className="villagePlots-filter__main">
                  <div className="filters-main-form">
                    <div className="filters-search__wrapper">
                      <div className="filters-search" style={{ width: '488.5px' }}>
                        <div className="field-root field-root_full-width">
                          <div className="field-wrapper field-wrapper_press-effect-animation text-field">
                            <div className="field-helper field-info">
                              <div className="field-wrapper__shell">
                                <div className="field field_lg px-2">
                                  <div className="field__icon field__icon_before">
                                    <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                                      <path d="M8.50012 3.25C5.60063 3.25 3.25012 5.6005 3.25012 8.5C3.25012 11.3995 5.60063 13.75 8.50012 13.75C11.3996 13.75 13.7501 11.3995 13.7501 8.5C13.7501 5.6005 11.3996 3.25 8.50012 3.25ZM1.75012 8.5C1.75012 4.77208 4.7722 1.75 8.50012 1.75C12.228 1.75 15.2501 4.77208 15.2501 8.5C15.2501 10.0938 14.6978 11.5585 13.774 12.7133L18.5305 17.4697C18.8233 17.7626 18.8233 18.2374 18.5305 18.5303C18.2376 18.8232 17.7627 18.8232 17.4698 18.5303L12.7134 13.7739C11.5586 14.6976 10.0939 15.25 8.50012 15.25C4.7722 15.25 1.75012 12.2279 1.75012 8.5Z" fill="#4C4C4C"/>
                                    </svg>
                                  </div>
                                  <div className="field__element">
                                    <input type="text" placeholder="Поиск по номеру участка" className="text-field__element" autoComplete="off" />
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div className="filters-main-form__fields">
                      <div className="filters-main-form__field">
                        <div className="main-field-wrapper" style={{ minWidth: '159.21875px' }}>
                          <div className="field-root field-root_full-width">
                            <div className="field-wrapper field-wrapper_press-effect-animation select">
                              <div className="field-helper field-info">
                                <div className="field-wrapper__shell field-wrapper__shell_disabled">
                                  <div tabIndex="0" role="select" className="field field_disabled field_md px-4">
                                    <div className="field__element">
                                      <input type="hidden" data-role="select" name="land_purpose" value="" />
                                      <span className="select__placeholder">Назначение земли</span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="filters-main-form__field">
                        <div className="main-field-wrapper" style={{ minWidth: '125.921875px' }}>
                          <div className="field-root field-root_full-width">
                            <div className="field-wrapper field-wrapper_press-effect-animation range-select">
                              <div className="field-helper field-info">
                                <div className="field-wrapper__shell">
                                  <div tabIndex="0" role="select" className="field field_md px-4">
                                    <div className="field__element">
                                      <input type="hidden" data-role="range-select" name="price" value="" />
                                      <span>Цена от-до, ₽</span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="filters-main-form__field">
                        <div className="main-field-wrapper" style={{ minWidth: '150.125px' }}>
                          <div className="field-root field-root_full-width">
                            <div className="field-wrapper field-wrapper_press-effect-animation range-select range-field">
                              <div className="field-helper field-info">
                                <div className="field-wrapper__shell">
                                  <div tabIndex="0" role="select" className="field field_md px-4">
                                    <div className="field__element">
                                      <input type="hidden" data-role="range-select" name="plot_square" value="" />
                                      <span>Площадь участка</span>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div className="filters-main-form__field filters-main-form__field_toggle">
                        <button className="btn px-4" type="button">
                          <span className="btn__content">
                            <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                              <path fillRule="evenodd" clipRule="evenodd" d="M3.75012 2C4.16434 2 4.50012 2.33579 4.50012 2.75V8.25273C4.50012 8.66695 4.16434 9.00273 3.75012 9.00273C3.33591 9.00273 3.00012 8.66695 3.00012 8.25273V2.75C3.00012 2.33579 3.33591 2 3.75012 2ZM10.0001 2C10.4143 2 10.7501 2.33579 10.7501 2.75V6.5H12.0001C12.4143 6.5 12.7501 6.83579 12.7501 7.25C12.7501 7.66421 12.4143 8 12.0001 8H8.00012C7.58591 8 7.25012 7.66421 7.25012 7.25C7.25012 6.83579 7.58591 6.5 8.00012 6.5H9.25012V2.75C9.25012 2.33579 9.58591 2 10.0001 2ZM16.2501 2C16.6643 2 17.0001 2.33579 17.0001 2.75V10.25C17.0001 10.6642 16.6643 11 16.2501 11C15.8359 11 15.5001 10.6642 15.5001 10.25V2.75C15.5001 2.33579 15.8359 2 16.2501 2ZM10.0001 9.5C10.4143 9.5 10.7501 9.83579 10.7501 10.25L10.7501 17.25C10.7501 17.6642 10.4143 18 10.0001 18C9.58591 18 9.25012 17.6642 9.25012 17.25L9.25012 10.25C9.25012 9.83579 9.58591 9.5 10.0001 9.5ZM1.00012 11.25C1.00012 10.8358 1.33591 10.5 1.75012 10.5H5.75012C6.16434 10.5 6.50012 10.8358 6.50012 11.25C6.50012 11.6642 6.16434 12 5.75012 12H4.50012V17.25C4.50012 17.6642 4.16434 18 3.75012 18C3.33591 18 3.00012 17.6642 3.00012 17.25L3.00012 12H1.75012C1.33591 12 1.00012 11.6642 1.00012 11.25ZM13.5001 13.25C13.5001 12.8358 13.8359 12.5 14.2501 12.5H18.2501C18.6643 12.5 19.0001 12.8358 19.0001 13.25C19.0001 13.6642 18.6643 14 18.2501 14H17.0001V17.25C17.0001 17.6642 16.6643 18 16.2501 18C15.8359 18 15.5001 17.6642 15.5001 17.25V14H14.2501C13.8359 14 13.5001 13.6642 13.5001 13.25Z" fill="#4C4C4C"/>
                            </svg>
                            <span>Все фильтры</span>
                          </span>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div className="village-plots-section__section container">
        <div className="village-plots-section__plots-header align-items-center row">
          <div className="col">
            <h2 className="village-plots-section__plots-title"></h2>
          </div>
        </div>
        <div className="row">
          <div id="accordion-container-id" className="village-plots-section__accordions-container">
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
          </div>
        </div>
      </div>
    </div>
  )
}

export default ObjectPlots
