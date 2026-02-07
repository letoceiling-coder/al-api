import { useState, useMemo } from 'react'
import './ApartmentsTable.css'
import { getImageUrl } from '../../utils/imageUtils'
import HousesPlans from './HousesPlans'

const ApartmentsTable = ({ apartmentsData, unifiedData, objectType, plansData, blockId, blockGuid }) => {
  const [viewType, setViewType] = useState('table') // 'table', 'plan'
  const [sortBy, setSortBy] = useState('price') // 'price', 'area', 'deadline'
  const [sortOrder, setSortOrder] = useState('asc') // 'asc', 'desc'
  const [expandedGroups, setExpandedGroups] = useState(new Set())

  // Извлекаем квартиры из различных структур ответа
  const apartments = useMemo(() => {
    if (!apartmentsData) return []
    
    // Проверяем grouped_data (структура с группировкой)
    if (apartmentsData.grouped_data && typeof apartmentsData.grouped_data === 'object') {
      const allApartments = []
      Object.values(apartmentsData.grouped_data).forEach(group => {
        if (Array.isArray(group)) {
          group.forEach(item => {
            if (item.apartments && Array.isArray(item.apartments)) {
              allApartments.push(...item.apartments)
            } else if (item.id || item._id) {
              allApartments.push(item)
            }
          })
        } else if (group.apartments && Array.isArray(group.apartments)) {
          allApartments.push(...group.apartments)
        }
      })
      return allApartments
    }
    
    // Плоский массив
    if (Array.isArray(apartmentsData.data)) {
      return apartmentsData.data
    }
    if (Array.isArray(apartmentsData.apartments?.data)) {
      return apartmentsData.apartments.data
    }
    if (Array.isArray(apartmentsData.apartments?.results)) {
      return apartmentsData.apartments.results
    }
    if (Array.isArray(apartmentsData.results)) {
      return apartmentsData.results
    }
    if (Array.isArray(apartmentsData)) {
      return apartmentsData
    }
    
    return []
  }, [apartmentsData])

  // Группируем квартиры по: очередь • корпус • секция • срок сдачи
  const groupedApartments = useMemo(() => {
    const groups = new Map()
    
    apartments.forEach(apt => {
      const queue = apt.queue || apt.queue_name || '1'
      const building = apt.building_name || apt.building || apt.corpus || '—'
      const section = apt.section_name || apt.section || '—'
      
      // Обрабатываем deadline
      let deadline = '—'
      if (apt.deadline) {
        if (typeof apt.deadline === 'string') {
          deadline = apt.deadline
        } else if (Array.isArray(apt.deadline)) {
          const deadlineItem = apt.deadline.find(d => d.deadline || d.value)
          deadline = deadlineItem?.deadline || deadlineItem?.value || '—'
        } else if (apt.deadline.deadline || apt.deadline.value) {
          deadline = apt.deadline.deadline || apt.deadline.value
        }
      }
      
      const groupKey = `${queue}#${building}#${section}#${deadline}`
      
      if (!groups.has(groupKey)) {
        groups.set(groupKey, {
          key: groupKey,
          queue,
          building,
          section,
          deadline,
          apartments: [],
        })
      }
      
      groups.get(groupKey).apartments.push(apt)
    })
    
    // Сортируем квартиры внутри групп
    groups.forEach(group => {
      group.apartments.sort((a, b) => {
        const aPrice = a.base_price || a.price || 0
        const bPrice = b.base_price || b.price || 0
        
        if (sortBy === 'price') {
          return sortOrder === 'asc' ? aPrice - bPrice : bPrice - aPrice
        }
        
        return 0
      })
    })
    
    return Array.from(groups.values())
  }, [apartments, sortBy, sortOrder])

  const toggleGroup = (groupKey) => {
    const newExpanded = new Set(expandedGroups)
    if (newExpanded.has(groupKey)) {
      newExpanded.delete(groupKey)
    } else {
      newExpanded.add(groupKey)
    }
    setExpandedGroups(newExpanded)
  }

  const formatPrice = (price) => {
    if (!price || price === 0) return 'По запросу'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const formatPricePerM2 = (price, area) => {
    if (!price || !area || area === 0) return '—'
    const perM2 = Math.round(price / area)
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(perM2)
  }

  const getImageUrlForApartment = (apt) => {
    if (apt.image?.url) return getImageUrl(apt.image.url)
    if (apt.images && Array.isArray(apt.images) && apt.images.length > 0) {
      return getImageUrl(apt.images[0])
    }
    if (apt.image && typeof apt.image === 'string') {
      return getImageUrl(apt.image)
    }
    if (apt.plan) return getImageUrl(apt.plan)
    if (apt.plan_image) return getImageUrl(apt.plan_image)
    return null
  }

  const getStatus = (apt) => {
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

  const getStatusClass = (status) => {
    const statusLower = String(status).toLowerCase().replace(/\s+/g, '-')
    if (statusLower.includes('свободн') || statusLower.includes('available')) {
      return 'status-available'
    }
    if (statusLower.includes('забронирован') || statusLower.includes('booked')) {
      return 'status-booked'
    }
    if (statusLower.includes('продан') || statusLower.includes('sold')) {
      return 'status-sold'
    }
    return 'status-default'
  }

  return (
    <div className="apartments-table-container">
      <div className="apartments-controls">
        {/* Переключатель видов */}
        <div className="view-type-buttons">
          <button
            className={`view-type-btn ${viewType === 'table' ? 'active' : ''}`}
            onClick={() => setViewType('table')}
          >
            Таблица
          </button>
          <button
            className={`view-type-btn ${viewType === 'plan' ? 'active' : ''}`}
            onClick={() => setViewType('plan')}
          >
            Планировки
          </button>
        </div>

        {/* Кнопка "Квартиры на шахматке" */}
        {(blockId || unifiedData?._id || unifiedData?.id) && (
          <a
            href={`/trendagent/apartments/${blockId || unifiedData._id || unifiedData.id}/checkerboard${blockGuid || unifiedData?.guid ? `?guid=${blockGuid || unifiedData.guid}` : ''}`}
            target="_blank"
            rel="noopener noreferrer"
            className="checkerboard-link"
          >
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M3.54862 2.5C2.96955 2.5 2.50012 2.96943 2.50012 3.5485V5.5H9.50012V2.5H3.54862ZM11.0001 2.5V9.5H17.5001V3.5485C17.5001 2.96943 17.0307 2.5 16.4516 2.5H11.0001ZM17.5001 11H11.0001V13.5H17.5001V11ZM17.5001 15H11.0001V17.5H16.4516C17.0307 17.5 17.5001 17.0306 17.5001 16.4515V15ZM9.50012 17.5V11H2.50012V16.4515C2.50012 17.0306 2.96955 17.5 3.54862 17.5H9.50012ZM2.50012 9.5H9.50012V7H2.50012V9.5ZM1.00012 3.5485C1.00012 2.141 2.14112 1 3.54862 1H16.4516C17.8591 1 19.0001 2.141 19.0001 3.5485V16.4515C19.0001 17.859 17.8591 19 16.4516 19H3.54862C2.14112 19 1.00012 17.859 1.00012 16.4515V3.5485Z" fill="currentColor"/>
            </svg>
            <span>Квартиры на шахматке</span>
          </a>
        )}
        
        {/* Кнопка сортировки (только для таблицы) */}
        {viewType === 'table' && (
          <div className="sort-control">
            <button
              className="sort-btn"
              onClick={() => {
                if (sortBy === 'price') {
                  setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')
                } else {
                  setSortBy('price')
                  setSortOrder('asc')
                }
              }}
            >
              По цене {sortBy === 'price' && (sortOrder === 'asc' ? '↑' : '↓')}
            </button>
          </div>
        )}
      </div>

      {/* Таблица */}
      {viewType === 'table' && (
        <div className="apartments-table-wrapper">
          {groupedApartments.length > 0 ? (
            groupedApartments.map((group) => {
              const isExpanded = expandedGroups.has(group.key)
              const groupTitle = `${group.queue} очередь • ${group.building} • ${group.section} • ${group.deadline}`
              
              return (
                <div key={group.key} className="apartments-group">
                  <div
                    className="apartments-group-header"
                    onClick={() => toggleGroup(group.key)}
                  >
                    <div className="group-header-left">
                      <span className={`expand-icon ${isExpanded ? 'expanded' : ''}`}>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                          <path d="M6 4L10 8L6 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                      </span>
                      <span className="group-title">{groupTitle}</span>
                    </div>
                    <span className="group-count">{group.apartments.length} {group.apartments.length === 1 ? 'квартира' : group.apartments.length < 5 ? 'квартиры' : 'квартир'}</span>
                  </div>
                  
                  {isExpanded && (
                    <div className="apartments-table-container-inner">
                      <table className="apartments-table">
                        <thead>
                          <tr>
                            <th className="col-image">План</th>
                            <th className="col-corpus">Корпус</th>
                            <th className="col-section">Секция</th>
                            <th className="col-floor">Этаж</th>
                            <th className="col-number">№ кв.</th>
                            <th className="col-area">S прив.</th>
                            <th className="col-kitchen">S кухни</th>
                            <th className="col-finishing">Отделка</th>
                            <th className="col-base-price">Базовая цена</th>
                            <th className="col-full-price">100% цена</th>
                            <th className="col-price-m2">За м²</th>
                            <th className="col-exclusive">Экскл.</th>
                            <th className="col-status">Статус</th>
                            <th className="col-view">Вид</th>
                            <th className="col-actions">Действия</th>
                          </tr>
                        </thead>
                        <tbody>
                          {group.apartments.map((apt, idx) => {
                            const imageUrl = getImageUrlForApartment(apt)
                            const number = apt.number || apt.apartment_number || '—'
                            const floor = apt.floor || '—'
                            const section = apt.section_name || apt.section || '—'
                            const building = apt.building_name || apt.building || apt.corpus || '—'
                            const area = apt.privArea || apt.area || apt.area_total || null
                            const kitchenArea = apt.kitchenArea || apt.kitchen_area || null
                            const finishing = apt.finishing_name || apt.finishing || '—'
                            const basePrice = apt.base_price || null
                            const fullPrice = apt.price || apt.full_price || null
                            const pricePerM2 = area && basePrice ? formatPricePerM2(basePrice, area) : '—'
                            const exclusive = apt.exclusive || apt.is_exclusive ? 'Да' : 'Нет'
                            const status = getStatus(apt)
                            const view = apt.view_image || apt.view || '—'
                            
                            return (
                              <tr key={apt.id || apt._id || idx}>
                                <td className="col-image">
                                  {imageUrl ? (
                                    <img 
                                      src={imageUrl} 
                                      alt={`План ${number}`} 
                                      className="plan-thumbnail"
                                      onError={(e) => {
                                        e.target.style.display = 'none'
                                        const placeholder = document.createElement('div')
                                        placeholder.className = 'plan-placeholder'
                                        placeholder.textContent = '—'
                                        e.target.parentNode.appendChild(placeholder)
                                      }}
                                    />
                                  ) : (
                                    <div className="plan-placeholder">—</div>
                                  )}
                                </td>
                                <td className="col-corpus">{building}</td>
                                <td className="col-section">{section}</td>
                                <td className="col-floor">{floor}</td>
                                <td className="col-number">{number}</td>
                                <td className="col-area">{area ? `${area} м²` : '—'}</td>
                                <td className="col-kitchen">{kitchenArea ? `${kitchenArea} м²` : '—'}</td>
                                <td className="col-finishing">{finishing}</td>
                                <td className="col-base-price">{basePrice ? formatPrice(basePrice) : 'По запросу'}</td>
                                <td className="col-full-price">{fullPrice ? formatPrice(fullPrice) : '—'}</td>
                                <td className="col-price-m2">{pricePerM2}</td>
                                <td className="col-exclusive">{exclusive}</td>
                                <td className="col-status">
                                  <span className={`status-badge ${getStatusClass(status)}`}>
                                    {status}
                                  </span>
                                </td>
                                <td className="col-view">{view}</td>
                                <td className="col-actions">
                                  <a
                                    href={`/trendagent/apartments/${blockId || unifiedData?._id || unifiedData?.id}/flat/${apt.id || apt._id}${blockGuid || unifiedData?.guid ? `?block=${blockId || unifiedData._id || unifiedData.id}&guid=${blockGuid || unifiedData.guid}` : `?block=${blockId || unifiedData._id || unifiedData.id}`}`}
                                    className="flat-link"
                                    title="Детальная информация"
                                  >
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                      <path d="M6 3L11 8L6 13" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    </svg>
                                  </a>
                                  <button className="compare-btn" title="Добавить к сравнению">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                      <path d="M8 2V14M2 8H14" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                                    </svg>
                                  </button>
                                </td>
                              </tr>
                            )
                          })}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              )
            })
          ) : (
            <div className="empty-state">
              <p>Квартиры не найдены</p>
            </div>
          )}
        </div>
      )}

      {/* Планировки */}
      {viewType === 'plan' && (
        <HousesPlans plansData={plansData} apartmentsData={apartmentsData} />
      )}
    </div>
  )
}

export default ApartmentsTable
