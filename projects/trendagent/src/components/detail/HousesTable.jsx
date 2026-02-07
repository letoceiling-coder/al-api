import { useState, useMemo } from 'react'
import './HousesTable.css'
import { getImageUrl } from '../../utils/imageUtils'
import HousesPlans from './HousesPlans'

const HousesTable = ({ housesData, unifiedData, objectType, plansData, blockId, blockGuid }) => {
  const [viewType, setViewType] = useState('table') // 'table', 'plan', 'checkerboard'
  const [sortBy, setSortBy] = useState('price') // 'price', 'area', 'deadline'
  const [sortOrder, setSortOrder] = useState('asc') // 'asc', 'desc'
  const [expandedGroups, setExpandedGroups] = useState(new Set())

  if (!housesData && !unifiedData) {
    return null
  }

  const formatPrice = (price) => {
    if (!price || price === 0) return '—'
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
  // Согласно TRENDAGENT_PAGE_STRUCTURE.md, данные приходят в grouped_data с ключами типа "1#4 кв. 2024"
  let houses = []
  let groupedData = null
  
  if (housesData) {
    // Проверяем наличие grouped_data (группированная структура из API)
    if (housesData.grouped_data && typeof housesData.grouped_data === 'object') {
      groupedData = housesData.grouped_data
    } else if (housesData.apartments?.grouped_data) {
      groupedData = housesData.apartments.grouped_data
    }
    
    // Получаем плоский массив для обратной совместимости
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

  // Группируем дома по структуре из API (grouped_data) или по корпусам/секциям
  const groupedHouses = useMemo(() => {
    const groups = []
    
    // Если есть grouped_data из API, используем его структуру
    if (groupedData && typeof groupedData === 'object') {
      Object.keys(groupedData).forEach((groupKey) => {
        const groupsArray = groupedData[groupKey]
        if (Array.isArray(groupsArray)) {
          groupsArray.forEach((group) => {
            if (group.apartments && Array.isArray(group.apartments)) {
              // Извлекаем информацию о группе из первой квартиры
              const firstApartment = group.apartments[0]
              const building = firstApartment?.building_name || firstApartment?.building || firstApartment?.corpus || '—'
              const section = firstApartment?.section_name || firstApartment?.section || '—'
              const deadline = firstApartment?.deadline 
                ? (Array.isArray(firstApartment.deadline) && firstApartment.deadline.length > 0
                  ? formatDate(firstApartment.deadline[0].deadline || firstApartment.deadline[0].value || firstApartment.deadline[0])
                  : formatDate(firstApartment.deadline))
                : null
              const queue = firstApartment?.queue || firstApartment?.queue_name || null
              
              // Формируем заголовок группы: "1 очередь • Корпус 14 • 2 кв. 2026"
              let groupTitle = ''
              const groupParts = []
              if (queue) groupParts.push(`${queue} очередь`)
              if (building && building !== '—') groupParts.push(`Корпус ${building}`)
              if (section && section !== '—') groupParts.push(`Секция ${section}`)
              if (deadline) {
                const deadlineParts = deadline.split(' ')
                if (deadlineParts.length >= 2) {
                  groupParts.push(`${deadlineParts[1]} ${deadlineParts[0]}`)
                } else {
                  groupParts.push(deadline)
                }
              }
              groupTitle = groupParts.join(' • ') || groupKey
              
              groups.push({
                key: groupTitle,
                houses: group.apartments,
                building,
                section,
                deadline,
                queue,
              })
            }
          })
        }
      })
    } else {
      // Если нет grouped_data, группируем вручную
      const groupsMap = {}
      
      houses.forEach((house) => {
        const building = house.building_name || house.building || house.corpus || '—'
        const section = house.section_name || house.section || '—'
        const deadline = house.deadline 
          ? (Array.isArray(house.deadline) && house.deadline.length > 0
            ? formatDate(house.deadline[0].deadline || house.deadline[0].value || house.deadline[0])
            : formatDate(house.deadline))
          : null
        const queue = house.queue || house.queue_name || null
        
        let groupKey = ''
        const groupParts = []
        if (queue) groupParts.push(`${queue} очередь`)
        if (building && building !== '—') groupParts.push(`Корпус ${building}`)
        if (section && section !== '—') groupParts.push(`Секция ${section}`)
        if (deadline) {
          const deadlineParts = deadline.split(' ')
          if (deadlineParts.length >= 2) {
            groupParts.push(`${deadlineParts[1]} ${deadlineParts[0]}`)
          } else {
            groupParts.push(deadline)
          }
        }
        groupKey = groupParts.join(' • ') || 'Без группировки'
        
        if (!groupsMap[groupKey]) {
          groupsMap[groupKey] = {
            key: groupKey,
            houses: [],
            building,
            section,
            deadline,
            queue,
          }
        }
        
        groupsMap[groupKey].houses.push(house)
      })
      
      return Object.values(groupsMap)
    }
    
    return groups
  }, [houses, groupedData])

  // Сортируем группы и дома внутри групп
  const sortedGroups = useMemo(() => {
    return groupedHouses.map(group => {
      const sortedHouses = [...group.houses].sort((a, b) => {
        let aValue, bValue
        
        if (sortBy === 'price') {
          aValue = a.price || (a.min_prices && a.min_prices.length > 0 ? (a.min_prices[0].price || a.min_prices[0].value) : 0) || 0
          bValue = b.price || (b.min_prices && b.min_prices.length > 0 ? (b.min_prices[0].price || b.min_prices[0].value) : 0) || 0
        } else if (sortBy === 'area') {
          aValue = a.area || a.privArea || 0
          bValue = b.area || b.privArea || 0
        } else {
          aValue = 0
          bValue = 0
        }
        
        return sortOrder === 'asc' ? aValue - bValue : bValue - aValue
      })
      
      return { ...group, houses: sortedHouses }
    })
  }, [groupedHouses, sortBy, sortOrder])

  const toggleGroup = (groupKey) => {
    const newExpanded = new Set(expandedGroups)
    if (newExpanded.has(groupKey)) {
      newExpanded.delete(groupKey)
    } else {
      newExpanded.add(groupKey)
    }
    setExpandedGroups(newExpanded)
  }

  const hasHouses = houses.length > 0

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
      <div className="houses-table-header">
        <h2>Дома</h2>
        
        {/* Переключатель видов */}
        <div className="houses-view-controls">
          <div className="view-type-buttons">
            <button
              className={`view-type-btn ${viewType === 'table' ? 'active' : ''}`}
              onClick={() => setViewType('table')}
            >
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path fillRule="evenodd" clipRule="evenodd" d="M2.00012 4.75C2.00012 4.33579 2.33591 4 2.75012 4H2.75846C3.17267 4 3.50846 4.33579 3.50846 4.75C3.50846 5.16421 3.17267 5.5 2.75846 5.5H2.75012C2.33591 5.5 2.00012 5.16421 2.00012 4.75ZM6.00012 4.75C6.00012 4.33579 6.33591 4 6.75012 4H17.2501C17.6643 4 18.0001 4.33579 18.0001 4.75C18.0001 5.16421 17.6643 5.5 17.2501 5.5H6.75012C6.33591 5.5 6.00012 5.16421 6.00012 4.75ZM2.00012 9.75C2.00012 9.33579 2.33591 9 2.75012 9H2.75846C3.17267 9 3.50846 9.33579 3.50846 9.75C3.50846 10.1642 3.17267 10.5 2.75846 10.5H2.75012C2.33591 10.5 2.00012 10.1642 2.00012 9.75ZM6.00012 9.75C6.00012 9.33579 6.33591 9 6.75012 9H17.2501C17.6643 9 18.0001 9.33579 18.0001 9.75C18.0001 10.1642 17.6643 10.5 17.2501 10.5H6.75012C6.33591 10.5 6.00012 10.1642 6.00012 9.75ZM2.00012 14.75C2.00012 14.3358 2.33591 14 2.75012 14H2.75846C3.17267 14 3.50846 14.3358 3.50846 14.75C3.50846 15.1642 3.17267 15.5 2.75846 15.5H2.75012C2.33591 15.5 2.00012 15.1642 2.00012 14.75ZM6.00012 14.75C6.00012 14.3358 6.33591 14 6.75012 14H17.2501C17.6643 14 18.0001 14.3358 18.0001 14.75C18.0001 15.1642 17.6643 15.5 17.2501 15.5H6.75012C6.33591 15.5 6.00012 15.1642 6.00012 14.75Z" fill="currentColor"/>
              </svg>
              <span>Таблица</span>
            </button>
            <button
              className={`view-type-btn ${viewType === 'plan' ? 'active' : ''}`}
              onClick={() => setViewType('plan')}
            >
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path fillRule="evenodd" clipRule="evenodd" d="M1.00012 2.75C1.00012 1.7835 1.78362 1 2.75012 1H7.25012C8.21662 1 9.00012 1.7835 9.00012 2.75V7.25C9.00012 8.2165 8.21662 9 7.25012 9H2.75012C1.78362 9 1.00012 8.2165 1.00012 7.25V2.75ZM11.0001 2.75C11.0001 1.7835 11.7836 1 12.7501 1H17.2501C18.2166 1 19.0001 1.7835 19.0001 2.75V7.25C19.0001 8.2165 18.2166 9 17.2501 9H12.7501C11.7836 9 11.0001 8.2165 11.0001 7.25V2.75ZM1.00012 12.75C1.00012 11.7835 1.78362 11 2.75012 11H7.25012C8.21662 11 9.00012 11.7835 9.00012 12.75V17.25C9.00012 18.2165 8.21662 19 7.25012 19H2.75012C1.78362 19 1.00012 18.2165 1.00012 17.25V12.75ZM11.0001 12.75C11.0001 11.7835 11.7836 11 12.7501 11H17.2501C18.2166 11 19.0001 11.7835 19.0001 12.75V17.25C19.0001 18.2165 18.2166 19 17.2501 19H12.7501C11.7836 19 11.0001 18.2165 11.0001 17.25V12.75Z" fill="currentColor"/>
              </svg>
              <span>Планировки</span>
            </button>
          </div>
          
          {/* Кнопка "Квартиры на шахматке" */}
          {(blockId || unifiedData?._id || unifiedData?.id) && (
            <a
              href={`/trendagent/houses/${blockId || unifiedData._id || unifiedData.id}/checkerboard${blockGuid || unifiedData?.guid ? `?guid=${blockGuid || unifiedData.guid}` : ''}`}
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
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" className={sortOrder === 'asc' ? 'sort-asc' : 'sort-desc'}>
                  <path fillRule="evenodd" clipRule="evenodd" d="M10.0001 4.00391C10.4143 4.00391 10.7501 4.33969 10.7501 4.75391V14.3401L12.7005 12.2397C12.9824 11.9361 13.4569 11.9186 13.7605 12.2004C14.064 12.4823 14.0816 12.9568 13.7997 13.2603L10.5497 16.7603C10.4078 16.9132 10.2087 17 10.0001 17C9.79157 17 9.59244 16.9132 9.45053 16.7603L6.20053 13.2603C5.91868 12.9568 5.93625 12.4823 6.23979 12.2004C6.54332 11.9186 7.01787 11.9361 7.29972 12.2397L9.25013 14.3401V4.75391C9.25013 4.33969 9.58591 4.00391 10.0001 4.00391Z" fill="currentColor"/>
                </svg>
                <span>По цене</span>
              </button>
            </div>
          )}
        </div>
      </div>

      <div className="houses-table-content">
        {viewType === 'table' && (
          <div className="houses-table-wrapper">
            {sortedGroups.map((group) => {
              const isExpanded = expandedGroups.has(group.key)
              const housesCount = group.houses.length
              
              return (
                <div key={group.key} className="houses-group">
                  <div className="houses-group-header" onClick={() => toggleGroup(group.key)}>
                    <h3 className="group-title">
                      <span>{group.key}</span>
                      <span className="group-count">{housesCount} {housesCount === 1 ? 'дом' : housesCount < 5 ? 'дома' : 'домов'}</span>
                    </h3>
                    <svg 
                      className={`group-toggle ${isExpanded ? 'expanded' : ''}`}
                      width="20" 
                      height="20" 
                      viewBox="0 0 20 20" 
                      fill="none"
                    >
                      <path fillRule="evenodd" clipRule="evenodd" d="M10.0001 12.75C10.199 12.75 10.3898 12.671 10.5305 12.5303L14.0305 9.03033C14.3233 8.73744 14.3233 8.26256 14.0305 7.96967C13.7376 7.67678 13.2627 7.67678 12.9698 7.96967L10.0001 10.9393L7.03045 7.96967C6.73756 7.67678 6.26269 7.67678 5.96979 7.96967C5.6769 8.26256 5.6769 8.73744 5.96979 9.03033L9.46979 12.5303C9.61044 12.671 9.80121 12.75 10.0001 12.75Z" fill="currentColor"/>
                    </svg>
                  </div>
                  
                  {isExpanded && (
                    <div className="houses-group-content">
                      <div className="scrollable-table">
                        <table className="houses-table">
                          <thead>
                            <tr>
                              <th></th>
                              <th className="sticky">Корп.</th>
                              <th className="sticky">Секц.</th>
                              <th className="sticky">Эт.</th>
                              <th className="sticky">№ кв.</th>
                              <th className="sticky">S прив.</th>
                              <th className="sticky">S кухни</th>
                              <th className="sticky">Отделка</th>
                              <th className="sticky">Базовая</th>
                              <th className="sticky">При 100%</th>
                              <th className="sticky">За м²</th>
                              <th className="sticky">Экскл.</th>
                              <th className="sticky">Статус</th>
                              <th className="sticky">Вид</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            {group.houses.map((house, index) => {
                              const name = house.name || house.block_name || house.title || 'Дом'
                              const imageUrl = house.image?.url || 
                                            (house.images && house.images.length > 0 ? getImageUrl(house.images[0]) : null) ||
                                            (house.image && typeof house.image === 'string' ? house.image : null)
                              const building = house.building_name || house.building || house.corpus || '—'
                              const section = house.section_name || house.section || '—'
                              const floor = house.floor || '—'
                              const number = house.number || house.apartment_number || '—'
                              const privArea = house.privArea || house.area || house.area_total || null
                              const kitchenArea = house.kitchenArea || house.kitchen_area || null
                              const finishing = house.finishing || house.finishing_name || '—'
                              const basePrice = house.base_price || house.price || null
                              const fullPrice = house.price || house.full_price || basePrice
                              const pricePerM2 = privArea && fullPrice ? Math.round(fullPrice / privArea) : null
                              const exclusive = house.exclusive || house.is_exclusive ? 'Да' : '—'
                              
                              let status = 'Свободная'
                              let statusText = 'Свободная'
                              if (house.status) {
                                if (typeof house.status === 'string') {
                                  status = house.status
                                  statusText = house.status
                                } else if (typeof house.status === 'object' && house.status.name) {
                                  status = house.status.name
                                  statusText = house.status.name
                                }
                              } else if (house.booking_status) {
                                status = house.booking_status
                                statusText = house.booking_status
                              } else if (house.is_booked) {
                                status = 'Забронирован'
                                statusText = 'Забронирован'
                              }
                              
                              const statusClass = typeof status === 'string' 
                                ? status.toLowerCase().replace(/\s+/g, '-')
                                : 'available'
                              
                              const viewImage = house.view_image || house.view || null
                              
                              return (
                                <tr key={index} className="house-row">
                                  <td className="table-image">
                                    {imageUrl ? (
                                      <div className="house-plan-image">
                                        <img 
                                          src={imageUrl} 
                                          alt={name}
                                          onError={(e) => {
                                            e.target.style.display = 'none'
                                            const placeholder = document.createElement('div')
                                            placeholder.className = 'image-placeholder'
                                            placeholder.textContent = '—'
                                            e.target.parentNode.appendChild(placeholder)
                                          }}
                                        />
                                      </div>
                                    ) : (
                                      <div className="image-placeholder">—</div>
                                    )}
                                  </td>
                                  <td className="table-cell">{building}</td>
                                  <td className="table-cell">{section}</td>
                                  <td className="table-cell">{floor}</td>
                                  <td className="table-cell">{number}</td>
                                  <td className="table-cell">{privArea ? `${privArea} м²` : '—'}</td>
                                  <td className="table-cell">{kitchenArea ? `${kitchenArea} м²` : '—'}</td>
                                  <td className="table-cell">{finishing}</td>
                                  <td className="table-cell base-price">{basePrice ? formatPrice(basePrice) : '—'}</td>
                                  <td className="table-cell full-price">{fullPrice ? formatPrice(fullPrice) : '—'}</td>
                                  <td className="table-cell">{pricePerM2 ? formatPrice(pricePerM2) : '—'}</td>
                                  <td className="table-cell">{exclusive}</td>
                                  <td className="table-cell">
                                    <span className={`status-badge status-${statusClass}`}>
                                      {statusText}
                                    </span>
                                  </td>
                                  <td className="table-cell">
                                    {viewImage ? (
                                      <img src={getImageUrl(viewImage)} alt="Вид" className="view-icon" />
                                    ) : (
                                      <span>—</span>
                                    )}
                                  </td>
                                  <td className="table-actions">
                                    <button className="action-btn" title="Добавить к сравнению">
                                      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path fillRule="evenodd" clipRule="evenodd" d="M10.7247 2.00391L14.3248 2.00391C14.8667 2.00391 15.3038 2.0039 15.6578 2.03282C16.0222 2.06259 16.3423 2.1255 16.6384 2.27639C17.1088 2.51608 17.4913 2.89853 17.731 3.36893C17.8819 3.66508 17.9448 3.98517 17.9745 4.3496C18.0034 4.70352 18.0034 5.14058 18.0034 5.68244L18.0034 9.28262C18.0035 9.48688 18.0035 9.62596 17.9914 9.74861C17.8745 10.9356 16.9354 11.8747 15.7484 11.9916C15.6258 12.0037 15.4867 12.0037 15.2824 12.0036L15.2535 12.0036V11.0036C15.4975 11.0036 15.5833 11.003 15.6504 10.9964C16.3626 10.9263 16.9261 10.3628 16.9962 9.65062C17.0028 9.58346 17.0034 9.4977 17.0034 9.2537V5.70391C17.0034 5.13561 17.0031 4.73945 16.9779 4.43103C16.9531 4.12845 16.907 3.9546 16.8399 3.82293C16.6961 3.54068 16.4667 3.31121 16.1844 3.1674C16.0527 3.10031 15.8789 3.05422 15.5763 3.0295C15.2679 3.0043 14.8717 3.00391 14.3034 3.00391H10.7537C10.5097 3.00391 10.4239 3.00452 10.3567 3.01113C9.64454 3.08126 9.08107 3.64473 9.01094 4.35693C9.00433 4.42408 9.00372 4.50984 9.00372 4.75384C9.00372 4.84495 8.97935 4.93037 8.93677 5.00394L11.3249 5.00394C11.8668 5.00393 12.3039 5.00393 12.6578 5.03285C13.0223 5.06262 13.3424 5.12553 13.6385 5.27642C14.1089 5.51611 14.4914 5.89856 14.731 6.36896C14.8819 6.66511 14.9448 6.9852 14.9746 7.34963C15.0035 7.70355 15.0035 8.14061 15.0035 8.68247L15.0035 12.2826C15.0036 12.4869 15.0036 12.626 14.9915 12.7486C14.8746 13.9356 13.9355 14.8748 12.7485 14.9916C12.6259 15.0037 12.4868 15.0037 12.2825 15.0037L12.2536 15.0037V14.0037C12.4976 14.0037 12.5834 14.0031 12.6505 13.9964C13.3627 13.9263 13.9262 13.3628 13.9963 12.6506C14.0029 12.5835 14.0035 12.4977 14.0035 12.2537V8.70394C14.0035 8.13564 14.0031 7.73948 13.9779 7.43106C13.9532 7.12848 13.9071 6.95463 13.84 6.82295C13.6962 6.54071 13.4668 6.31124 13.1845 6.16743C13.0528 6.10034 12.879 6.05425 12.5764 6.02953C12.268 6.00433 11.8718 6.00394 11.3035 6.00394H7.75374C7.50975 6.00394 7.42398 6.00455 7.35683 6.01116C6.64463 6.08128 6.08116 6.64476 6.01103 7.35696C6.00442 7.42411 6.00381 7.50987 6.00381 7.75387C6.00381 7.84488 5.9795 7.93021 5.93701 8.00372H8.32503C8.86692 8.00372 9.304 8.00372 9.65794 8.03263C10.0224 8.06241 10.3425 8.12531 10.6386 8.27621C11.109 8.51589 11.4915 8.89834 11.7311 9.36875C11.882 9.66489 11.9449 9.98499 11.9747 10.3494C12.0036 10.7033 12.0036 11.1404 12.0036 11.6823V14.3249C12.0036 14.8667 12.0036 15.3038 11.9747 15.6578C11.9449 16.0222 11.882 16.3423 11.7311 16.6384C11.4915 17.1088 11.109 17.4913 10.6386 17.731C10.3425 17.8819 10.0224 17.9448 9.65794 17.9745C9.30403 18.0035 8.86701 18.0034 8.32519 18.0034H5.68249C5.14067 18.0034 4.7035 18.0035 4.34959 17.9745C3.98517 17.9448 3.66508 17.8819 3.36893 17.731C2.89853 17.4913 2.51607 17.1088 2.27639 16.6384C2.1255 16.3423 2.06259 16.0222 2.03282 15.6578C2.0039 15.3038 2.0039 14.8667 2.00391 14.3248V11.6823C2.0039 11.1404 2.0039 10.7034 2.03282 10.3494C2.06259 9.98499 2.1255 9.66489 2.27639 9.36875C2.51607 8.89834 2.89853 8.51589 3.36893 8.27621C3.66508 8.12531 3.98517 8.06241 4.34959 8.03263C4.55955 8.01548 4.79876 8.0085 5.07175 8.00567C5.02856 7.93172 5.00381 7.84568 5.00381 7.75387L5.00381 7.72495C5.00379 7.52069 5.00377 7.38161 5.01585 7.25897C5.13272 6.07197 6.07184 5.13285 7.25884 5.01597C7.38149 5.00389 7.52056 5.00391 7.72482 5.00394L8.07067 5.00394C8.02809 4.93037 8.00372 4.84495 8.00372 4.75384L8.00372 4.72492C8.00369 4.52066 8.00368 4.38159 8.01575 4.25894C8.13263 3.07194 9.07175 2.13282 10.2587 2.01594C10.3814 2.00387 10.5205 2.00388 10.7247 2.00391Z" fill="currentColor"/>
                                      </svg>
                                    </button>
                                  </td>
                                </tr>
                              )
                            })}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  )}
                </div>
              )
            })}
          </div>
        )}
        
        {viewType === 'plan' && (
          <HousesPlans 
            plansData={plansData}
            apartmentsData={housesData}
          />
        )}
      </div>
    </div>
  )
}

export default HousesTable
