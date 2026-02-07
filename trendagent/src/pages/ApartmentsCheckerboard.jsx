import { useState, useEffect, useMemo } from 'react'
import { useParams, useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import './ApartmentsCheckerboard.css'

const ApartmentsCheckerboard = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const [searchParams, setSearchParams] = useSearchParams()
  const guid = searchParams.get('guid')

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [buildings, setBuildings] = useState([])
  const [selectedBuilding, setSelectedBuilding] = useState(null)
  const [apartments, setApartments] = useState(null)
  const [objectName, setObjectName] = useState('')
  
  // Фильтры
  const [roomFilter, setRoomFilter] = useState([])
  const [priceFrom, setPriceFrom] = useState('')
  const [priceTo, setPriceTo] = useState('')
  const [deadlineFilter, setDeadlineFilter] = useState('')
  const [showBookings, setShowBookings] = useState(false)
  
  // Чекбоксы скрытия
  const [hideFiltered, setHideFiltered] = useState(false)
  const [hideSold, setHideSold] = useState(false)
  const [hideBooked, setHideBooked] = useState(false)
  
  // Масштаб
  const [scale, setScale] = useState(1)

  const phone = '+79045393434'
  const password = 'nwBvh4q'

  useEffect(() => {
    loadBuildings()
  }, [id])

  useEffect(() => {
    if (selectedBuilding) {
      loadApartments()
    }
  }, [selectedBuilding, id, roomFilter, priceFrom, priceTo, deadlineFilter, showBookings])

  const loadBuildings = async () => {
    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
        room: searchParams.getAll('room').length > 0 
          ? searchParams.getAll('room').map(r => parseInt(r))
          : undefined,
      }

      const response = await trendAgentAPI.getApartmentsCheckerboardBuildings(id, params)

      if (response.success) {
        let buildingsData = []
        
        if (Array.isArray(response.data)) {
          buildingsData = response.data
        } else if (response.data && typeof response.data === 'object') {
          if (Array.isArray(response.data.results)) {
            buildingsData = response.data.results
          } else if (Array.isArray(response.data.buildings)) {
            buildingsData = response.data.buildings
          } else if (Array.isArray(response.data.data)) {
            buildingsData = response.data.data
          } else if (response.data.id || response.data._id || response.data.building_id) {
            buildingsData = [response.data]
          }
        }
        
        setBuildings(buildingsData)
        
        // Получаем название объекта из первого корпуса или из детальной страницы
        if (buildingsData.length > 0) {
          const firstBuilding = buildingsData[0]
          const blockName = firstBuilding.block_name || firstBuilding.object_name || ''
          setObjectName(blockName)
        }
        
        if (buildingsData.length > 0 && !selectedBuilding) {
          const firstBuildingId = buildingsData[0].id || buildingsData[0]._id || buildingsData[0].building_id
          setSelectedBuilding(firstBuildingId)
        }
      } else {
        setError(response.message || 'Ошибка загрузки корпусов')
      }
    } catch (err) {
      console.error('Ошибка загрузки корпусов:', err)
      setError(err.message || 'Ошибка загрузки корпусов')
    } finally {
      setLoading(false)
    }
  }

  const loadApartments = async () => {
    if (!selectedBuilding) return

    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
        building_id: selectedBuilding,
      }

      if (searchParams.get('apartments-onrequest') === 'true') {
        params.onrequest = true
      }

      const response = await trendAgentAPI.getApartmentsCheckerboardApartments(id, params)

      if (response.success) {
        let apartmentsData = null
        
        if (Array.isArray(response.data)) {
          apartmentsData = response.data
        } else if (response.data && typeof response.data === 'object') {
          if (Array.isArray(response.data.apartments)) {
            apartmentsData = response.data.apartments
          } else if (Array.isArray(response.data.data)) {
            apartmentsData = response.data.data
          } else if (response.data.floors || response.data.sections) {
            apartmentsData = []
            const floors = response.data.floors || []
            const sections = response.data.sections || []
            
            floors.forEach(floor => {
              if (floor.apartments && Array.isArray(floor.apartments)) {
                apartmentsData.push(...floor.apartments)
              }
            })
            
            sections.forEach(section => {
              if (section.apartments && Array.isArray(section.apartments)) {
                apartmentsData.push(...section.apartments)
              }
            })
          } else {
            apartmentsData = [response.data]
          }
        }
        
        setApartments(apartmentsData)
      } else {
        setError(response.message || 'Ошибка загрузки квартир')
      }
    } catch (err) {
      console.error('Ошибка загрузки квартир:', err)
      setError(err.message || 'Ошибка загрузки квартир')
    } finally {
      setLoading(false)
    }
  }

  const formatPrice = (price) => {
    if (!price || price === 0) return '0'
    return new Intl.NumberFormat('ru-RU', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(price)
  }

  // Обработка данных для шахматки
  const processedData = useMemo(() => {
    if (!apartments || !Array.isArray(apartments) || apartments.length === 0) {
      return { sections: [] }
    }

    // Применяем фильтры
    let filteredApartments = apartments.filter(apt => {
      // Фильтр по комнатам
      if (roomFilter.length > 0) {
        const aptRooms = apt.rooms || apt.room || 0
        if (!roomFilter.includes(aptRooms)) return false
      }

      // Фильтр по цене
      const price = apt.price || apt.base_price || 0
      if (priceFrom && price < parseFloat(priceFrom)) return false
      if (priceTo && price > parseFloat(priceTo)) return false

      // Фильтр по сроку сдачи
      if (deadlineFilter) {
        const deadline = apt.deadline || apt.deadline_name || ''
        if (deadline !== deadlineFilter) return false
      }

      // Фильтр по статусу
      const status = apt.status?.name || apt.status || apt.booking_status || 'Свободная'
      const isSold = status.toLowerCase().includes('продан') || status.toLowerCase().includes('sold')
      const isBooked = status.toLowerCase().includes('забронирован') || status.toLowerCase().includes('booked')
      
      if (hideSold && isSold) return false
      if (hideBooked && isBooked) return false

      return true
    })

    // Группируем по секциям
    const sectionsMap = new Map()
    
    filteredApartments.forEach(apt => {
      const sectionName = apt.section_name || apt.section || 'Без секции'
      const deadline = apt.deadline || apt.deadline_name || '—'
      const sectionKey = `${sectionName}_${deadline}`
      
      if (!sectionsMap.has(sectionKey)) {
        sectionsMap.set(sectionKey, {
          name: sectionName,
          deadline,
          subsections: new Map(),
          apartments: []
        })
      }
      
      sectionsMap.get(sectionKey).apartments.push(apt)
    })

    // Обрабатываем подсекции и группируем по этажам и типам
    const sections = Array.from(sectionsMap.values()).map(section => {
      // Группируем по подсекциям (по finishing или другим признакам)
      const subsectionsMap = new Map()
      
      section.apartments.forEach(apt => {
        const finishing = apt.finishing_name || apt.finishing || 'Без отделки'
        const rooms = apt.rooms || apt.room || 0
        const area = apt.privArea || apt.area || apt.area_total || 0
        
        // Создаем ключ подсекции (может быть по finishing или комбинации)
        const subsectionKey = `${finishing}_${rooms}_${area}`
        
        if (!subsectionsMap.has(subsectionKey)) {
          subsectionsMap.set(subsectionKey, {
            id: apt.subsection_id || apt.id || Math.random().toString(),
            label: finishing === 'Чистовая' ? 'В' : finishing === 'Без отделки' ? 'З' : 'В, З',
            finishing,
            rooms,
            area,
            apartments: []
          })
        }
        
        subsectionsMap.get(subsectionKey).apartments.push(apt)
      })

      const subsections = Array.from(subsectionsMap.values())

      // Группируем квартиры по этажам для каждой подсекции
      subsections.forEach(subsection => {
        const floorsMap = new Map()
        
        subsection.apartments.forEach(apt => {
          const floor = apt.floor || 0
          if (!floorsMap.has(floor)) {
            floorsMap.set(floor, [])
          }
          floorsMap.get(floor).push(apt)
        })

        subsection.floors = Array.from(floorsMap.entries())
          .sort(([a], [b]) => b - a)
      })

      return {
        ...section,
        subsections
      }
    })

    return { sections }
  }, [apartments, roomFilter, priceFrom, priceTo, deadlineFilter, hideSold, hideBooked])

  if (loading && !apartments) {
    return (
      <div className="checkerboard-page">
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="checkerboard-page">
        <div className="error-message">
          <p>{error}</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  return (
    <div id="chessboard" className="checkerboard-page">
      <div className="checkboard-container">
        <div className="g-0 flex-nowrap row">
          {/* Левая панель фильтров */}
          <div className="col-auto">
            <div className="checkerboard-filter">
              <h2 className="checkerboard-filter__title">
                {objectName || 'Загрузка...'}
              </h2>
              <div className="checkerboard-filter__content">
                <div className="apartments-filter apartments-filter_chess">
                  <div className="apartments-filter__main">
                    <div className="filters-main-form">
                      <div className="filters-main-form__fields">
                        {/* Фильтр: Тип квартиры */}
                        <div className="filters-main-form__field">
                          <div className="field-wrapper field-wrapper_press-effect-animation select">
                            <div className="field field_md px-4">
                              <div className="field__icon field__icon_before">
                                <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                                  <path fillRule="evenodd" clipRule="evenodd" d="M4 2.5C4 2.22386 4.22386 2 4.5 2H15.5C15.7761 2 16 2.22386 16 2.5V17.5C16 17.7761 15.7761 18 15.5 18H4.5C4.22386 18 4 17.7761 4 17.5V2.5ZM5 3V17H15V3H5ZM12.5 8.25C12.7761 8.25 13 8.47386 13 8.75V11.25C13 11.5261 12.7761 11.75 12.5 11.75C12.2239 11.75 12 11.5261 12 11.25V8.75C12 8.47386 12.2239 8.25 12.5 8.25Z" fill="#4C4C4C"/>
                                </svg>
                              </div>
                              <div className="field__element">
                                <select 
                                  className="field-select"
                                  value=""
                                  onChange={(e) => {
                                    const value = e.target.value
                                    if (value) {
                                      setRoomFilter([...roomFilter, parseInt(value)])
                                    }
                                  }}
                                >
                                  <option value="">Тип квартиры</option>
                                  <option value="1">1-к.кв</option>
                                  <option value="2">2-к.кв</option>
                                  <option value="3">3-к.кв</option>
                                  <option value="4">4-к.кв</option>
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Фильтр: Цена от-до */}
                        <div className="filters-main-form__field">
                          <div className="field-wrapper field-wrapper_press-effect-animation range-select">
                            <div className="field field_md px-4">
                              <div className="field__icon field__icon_before">
                                <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                                  <path fillRule="evenodd" clipRule="evenodd" d="M2 2.5C2 2.22386 2.22386 2 2.5 2H10.3241C10.4567 2 10.5839 2.0527 10.6777 2.14649L17.3996 8.87019C17.7842 9.25714 18 9.78057 18 10.3261C18 10.8717 17.7842 11.3951 17.3996 11.7821L11.7889 17.3943C11.5971 17.5863 11.3694 17.7386 11.1187 17.8426C10.8681 17.9465 10.5994 18 10.328 18C10.0566 18 9.78792 17.9465 9.53725 17.8426C9.28665 17.7386 9.05898 17.5864 8.86726 17.3944L2.14661 10.6798C2.05274 10.5861 2 10.4588 2 10.3261V2.5ZM3 3V10.1189L9.57428 16.6872C9.67318 16.7863 9.79102 16.8652 9.92027 16.9188C10.0495 16.9724 10.1881 17 10.328 17C10.4679 17 10.6065 16.9724 10.7357 16.9188C10.865 16.8652 10.9824 16.7867 11.0813 16.6876L16.6903 11.0772C16.8883 10.8776 17 10.6073 17 10.3261C17 10.0449 16.8888 9.77509 16.6908 9.57556L10.1169 3H3Z" fill="#4C4C4C"/>
                                </svg>
                              </div>
                              <div className="field__element">
                                <input 
                                  type="text" 
                                  placeholder="Цена от-до, ₽"
                                  className="field-input"
                                  value={priceFrom && priceTo ? `${priceFrom} - ${priceTo}` : ''}
                                  readOnly
                                />
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Фильтр: Срок сдачи */}
                        <div className="filters-main-form__field">
                          <div className="field-wrapper field-wrapper_press-effect-animation select">
                            <div className="field field_md px-4">
                              <div className="field__icon field__icon_before">
                                <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                                  <path fillRule="evenodd" clipRule="evenodd" d="M6.5 1C6.77614 1 7 1.22386 7 1.5V3H13V1.5C13 1.22386 13.2239 1 13.5 1C13.7761 1 14 1.22386 14 1.5V3H15.83C17.0266 3 18 3.97338 18 5.17V15.8313C18 17.0279 17.0266 18.0013 15.83 18.0013H4.17C2.97338 18.0013 2 17.0279 2 15.8313V5.17C2 3.97338 2.97338 3 4.17 3H6V1.5C6 1.22386 6.22386 1 6.5 1ZM6 4H4.17C3.52567 4 3 4.52567 3 5.17V8H17V5.17C17 4.52567 16.4743 4 15.83 4H14V5C14 5.27614 13.7761 5.5 13.5 5.5C13.2239 5.5 13 5.27614 13 5V4H7V5C7 5.27614 6.77614 5.5 6.5 5.5C6.22386 5.5 6 5.27614 6 5V4ZM17 9H3V15.8313C3 16.4756 3.52567 17.0013 4.17 17.0013H15.83C16.4743 17.0013 17 16.4756 17 15.8313V9Z" fill="#4C4C4C"/>
                                </svg>
                              </div>
                              <div className="field__element">
                                <select 
                                  className="field-select"
                                  value={deadlineFilter}
                                  onChange={(e) => setDeadlineFilter(e.target.value)}
                                >
                                  <option value="">Срок сдачи</option>
                                  <option value="Сдан">Сдан</option>
                                  <option value="Строится">Строится</option>
                                </select>
                              </div>
                            </div>
                          </div>
                        </div>

                        {/* Кнопка: Все фильтры */}
                        <div className="filters-main-form__field filters-main-form__field_toggle">
                          <button className="btn btn_white px-4" type="button">
                            <span className="btn__content justify-content-center">
                              <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                                <path fillRule="evenodd" clipRule="evenodd" d="M3.75012 2C4.16434 2 4.50012 2.33579 4.50012 2.75V8.25273C4.50012 8.66695 4.16434 9.00273 3.75012 9.00273C3.33591 9.00273 3.00012 8.66695 3.00012 8.25273V2.75C3.00012 2.33579 3.33591 2 3.75012 2Z" fill="#4C4C4C"/>
                              </svg>
                              <span>Все фильтры</span>
                            </span>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Чипсы с активными фильтрами */}
                  <div className="apartments-filter__results">
                    <div className="apartments-filter__tags">
                      <div className="chips">
                        {showBookings && (
                          <div className="chips__item chips__item_secondary">
                            <div className="chips__tag ps-2 pe-1 py-1">
                              <span className="btn">Показать брони</span>
                              <button 
                                className="chips__delete"
                                onClick={() => setShowBookings(false)}
                              >
                                <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                                  <path fillRule="evenodd" clipRule="evenodd" d="M13.8536 6.14645C14.0488 6.34171 14.0488 6.65829 13.8536 6.85355L10.707 10.0001L13.8536 13.1467C14.0488 13.342 14.0488 13.6585 13.8536 13.8538C13.6583 14.0491 13.3417 14.0491 13.1464 13.8538L9.99988 10.7072L6.85355 13.8536C6.65829 14.0488 6.34171 14.0488 6.14645 13.8536C5.95118 13.6583 5.95118 13.3417 6.14645 13.1464L9.29277 10.0001L6.14645 6.8538C5.95118 6.65854 5.95118 6.34195 6.14645 6.14669C6.34171 5.95143 6.65829 5.95143 6.85355 6.14669L9.99988 9.29302L13.1465 6.14645C13.3417 5.95118 13.6583 5.95118 13.8536 6.14645Z" fill="#4C4C4C"/>
                                </svg>
                              </button>
                            </div>
                          </div>
                        )}
                        {(roomFilter.length > 0 || priceFrom || priceTo || deadlineFilter) && (
                          <div className="chips__item">
                            <button 
                              className="btn btn_secondary px-2"
                              onClick={() => {
                                setRoomFilter([])
                                setPriceFrom('')
                                setPriceTo('')
                                setDeadlineFilter('')
                              }}
                            >
                              <span className="btn__content justify-content-center">
                                <span>Сбросить всё</span>
                              </span>
                            </button>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Основная область */}
          <div className="col">
            <div className="checkerboard-fixed">
              {/* Верхняя панель */}
              <div className="checkerboard-toppanel">
                {/* Навигация по корпусам */}
                <div className="checkerboard-toppanel__nav-list">
                  {buildings.map((building) => {
                    const buildingId = building.id || building._id || building.building_id
                    const apartmentsCount = building.apartments_count || building.count || 0
                    const isActive = selectedBuilding === buildingId
                    
                    return (
                      <button
                        key={buildingId}
                        className={`checkerboard-toppanel__nav-list__btn ${isActive ? 'checkerboard-toppanel__nav-list__btn__active' : ''}`}
                        onClick={() => setSelectedBuilding(buildingId)}
                      >
                        Корп. {buildings.indexOf(building) + 1}
                        <div className="badge" style={{ marginLeft: '5px' }}>
                          {apartmentsCount}
                        </div>
                      </button>
                    )
                  })}
                </div>

                {/* Чекбоксы скрытия */}
                <div className="checkerboard-toppanel-cb-hider cb-hider">
                  <div className="cb-hider__col">
                    <label className="checkbox checkbox_clear">
                      <input 
                        type="checkbox" 
                        className="checkbox__element"
                        checked={hideFiltered}
                        onChange={(e) => setHideFiltered(e.target.checked)}
                      />
                      <span className="checkbox__mark checkbox__mark_border">
                        {hideFiltered && (
                          <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                            <path fillRule="evenodd" clipRule="evenodd" d="M15.7165 6.2569C16.0168 6.54211 16.0291 7.01682 15.7439 7.3172L9.47727 13.9172C9.33809 14.0638 9.14563 14.148 8.94352 14.1507C8.7414 14.1534 8.54674 14.0745 8.40365 13.9317L5.27031 10.8054C4.97709 10.5128 4.97656 10.038 5.26912 9.74473C5.56169 9.45151 6.03656 9.45098 6.32978 9.74354L8.91887 12.3268L14.6562 6.28437C14.9414 5.98398 15.4161 5.97169 15.7165 6.2569Z" fill="#4C4C4C"/>
                          </svg>
                        )}
                      </span>
                      <span className="text-label text-label_md checkbox__label ms-2">
                        Скрыть не попавшие&nbsp;в&nbsp;фильтр
                      </span>
                    </label>
                  </div>
                  <div className="cb-hider__col cb-hider__col_sm">
                    <label className="checkbox checkbox_clear">
                      <input 
                        type="checkbox" 
                        className="checkbox__element"
                        checked={hideSold}
                        onChange={(e) => setHideSold(e.target.checked)}
                      />
                      <span className="checkbox__mark checkbox__mark_border">
                        {hideSold && (
                          <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                            <path fillRule="evenodd" clipRule="evenodd" d="M15.7165 6.2569C16.0168 6.54211 16.0291 7.01682 15.7439 7.3172L9.47727 13.9172C9.33809 14.0638 9.14563 14.148 8.94352 14.1507C8.7414 14.1534 8.54674 14.0745 8.40365 13.9317L5.27031 10.8054C4.97709 10.5128 4.97656 10.038 5.26912 9.74473C5.56169 9.45151 6.03656 9.45098 6.32978 9.74354L8.91887 12.3268L14.6562 6.28437C14.9414 5.98398 15.4161 5.97169 15.7165 6.2569Z" fill="#4C4C4C"/>
                          </svg>
                        )}
                      </span>
                      <span className="text-label text-label_md checkbox__label ms-2">
                        Скрыть проданные
                      </span>
                    </label>
                  </div>
                  <div className="cb-hider__col">
                    <label className="checkbox checkbox_clear checkbox_checked">
                      <input 
                        type="checkbox" 
                        className="checkbox__element"
                        checked={hideBooked}
                        onChange={(e) => setHideBooked(e.target.checked)}
                      />
                      <span className="checkbox__mark checkbox__mark_border checkbox__mark_checked">
                        {hideBooked && (
                          <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                            <path fillRule="evenodd" clipRule="evenodd" d="M15.7165 6.2569C16.0168 6.54211 16.0291 7.01682 15.7439 7.3172L9.47727 13.9172C9.33809 14.0638 9.14563 14.148 8.94352 14.1507C8.7414 14.1534 8.54674 14.0745 8.40365 13.9317L5.27031 10.8054C4.97709 10.5128 4.97656 10.038 5.26912 9.74473C5.56169 9.45151 6.03656 9.45098 6.32978 9.74354L8.91887 12.3268L14.6562 6.28437C14.9414 5.98398 15.4161 5.97169 15.7165 6.2569Z" fill="#4C4C4C"/>
                          </svg>
                        )}
                      </span>
                      <span className="text-label text-label_md checkbox__label ms-2">
                        Скрыть забронированные
                      </span>
                    </label>
                  </div>
                </div>
              </div>

              {/* Контейнер шахматки */}
              <div className={`checkerboard-container checkerboard-container-scale${scale}`}>
                {/* Кнопки масштабирования */}
                <div className="checkerboard-container__controls">
                  <button 
                    className="btn btn_white btn_onlyicon checkerboard-container__controls__plus"
                    onClick={() => setScale(Math.min(scale + 1, 3))}
                  >
                    <span className="btn__content justify-content-center">
                      <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                        <path fillRule="evenodd" clipRule="evenodd" d="M10.0001 3.25C10.4143 3.25 10.7501 3.58579 10.7501 4V9.5H16.2501C16.6643 9.5 17.0001 9.83579 17.0001 10.25C17.0001 10.6642 16.6643 11 16.2501 11H10.7501V16.5C10.7501 16.9142 10.4143 17.25 10.0001 17.25C9.58591 17.25 9.25012 16.9142 9.25012 16.5V11H3.75012C3.33591 11 3.00012 10.6642 3.00012 10.25C3.00012 9.83579 3.33591 9.5 3.75012 9.5H9.25012V4C9.25012 3.58579 9.58591 3.25 10.0001 3.25Z" fill="#4C4C4C"/>
                      </svg>
                    </span>
                  </button>
                  <button 
                    className="btn btn_disabled btn_white btn_onlyicon checkerboard-container__controls__minus"
                    onClick={() => setScale(Math.max(scale - 1, 1))}
                    disabled={scale <= 1}
                  >
                    <span className="btn__content justify-content-center">
                      <svg className="svg-icon" height="20" width="20" viewBox="0 0 20 20" fill="none">
                        <path fillRule="evenodd" clipRule="evenodd" d="M3.00012 10.25C3.00012 9.83579 3.33591 9.5 3.75012 9.5H16.2501C16.6643 9.5 17.0001 9.83579 17.0001 10.25C17.0001 10.6642 16.6643 11 16.2501 11H3.75012C3.33591 11 3.00012 10.6642 3.00012 10.25Z" fill="#4C4C4C"/>
                      </svg>
                    </span>
                  </button>
                </div>

                {/* Заголовки секций и подсекций */}
                <div className="g-0 row">
                  <div className="col-12">
                    <div className="checkerboard-container__sections-container">
                      <div className="checkerboard-container__sections">
                        {processedData.sections.map((section, sectionIdx) => (
                          <div key={sectionIdx} className="checkerboard-container__sections__item">
                            <div className="checkerboard-container__sections__name">
                              секция {section.name} - {section.deadline}
                            </div>
                            <div className="checkerboard-container__subsections">
                              {section.subsections.map((subsection, subIdx) => (
                                <div 
                                  key={subIdx} 
                                  id={subsection.id}
                                  className="checkerboard-container__subsections__item"
                                >
                                  {subsection.label}
                                </div>
                              ))}
                            </div>
                          </div>
                        ))}
                        <div className="checkerboard-container__sections__item--last"></div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Основная область с этажами и квартирами */}
                <div className="g-0 row">
                  {/* Номера этажей слева */}
                  <div className="col-auto">
                    <div className="checkerboard-container__floors-container">
                      <div className="checkerboard-container__floors">
                        {processedData.sections.length > 0 && processedData.sections[0].subsections.length > 0 && 
                         processedData.sections[0].subsections[0].floors.map(([floor]) => (
                          <div key={floor} id={`floor${floor}`} className="checkerboard-container__floors__item">
                            {floor}
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Область с квартирами */}
                  <div className="col">
                    <div className="checkerboard-container__area">
                      <div className="checkerboard-container__area__list">
                        {processedData.sections.map((section, sectionIdx) => (
                          <div key={sectionIdx} className="checkerboard__section">
                            <div className="checkerboard__apartments">
                              {section.subsections.map((subsection, subIdx) => (
                                <div key={subIdx} className="checkerboard__apartments-col">
                                  {subsection.floors.map(([floor, floorApartments]) => {
                                    const apartment = floorApartments[0] // Берем первую квартиру этого типа на этаже
                                    if (!apartment) {
                                      return (
                                        <div key={floor} className="checkerboard__item checkerboard__item_empty checkerboard__item_loading"></div>
                                      )
                                    }

                                    const status = apartment.status?.name || apartment.status || apartment.booking_status || 'Свободная'
                                    const isSold = status.toLowerCase().includes('продан') || status.toLowerCase().includes('sold')
                                    const isBooked = status.toLowerCase().includes('забронирован') || status.toLowerCase().includes('booked')
                                    const isAvailable = !isSold && !isBooked
                                    
                                    const bgColor = isSold ? 'rgb(0, 0, 0)' : 'rgb(255, 255, 255)'
                                    const textColor = isSold ? 'rgb(255, 255, 255)' : 'rgb(51, 51, 51)'

                                    return (
                                      <div key={floor} className="checkerboard__item">
                                        <div 
                                          className="checkerboard__item-content"
                                          style={{ backgroundColor: bgColor, color: textColor }}
                                        >
                                          <div className="checkerboard__item-topinfo">
                                            <div className="checkerboard__item-type" title={`${subsection.rooms}-к.кв`}>
                                              {subsection.rooms > 0 ? `${subsection.rooms}-к.кв` : ''}
                                            </div>
                                            <div className="checkerboard__item-number" title={`№ ${apartment.number || apartment.apartment_number || ''}`}>
                                              № {apartment.number || apartment.apartment_number || '—'}
                                            </div>
                                          </div>
                                          <div className="checkerboard__item-middleinfo">
                                            <div className="checkerboard__item-row">
                                              <div className="checkerboard__item-price">
                                                <span>{formatPrice(apartment.price || apartment.base_price || 0)}</span> ₽
                                              </div>
                                            </div>
                                            <div className="checkerboard__item-status" title={status}>
                                              {status}
                                            </div>
                                          </div>
                                          <div className="checkerboard__item-bottominfo">
                                            <div className={`checkerboard__item-finishing ${subsection.finishing === 'Чистовая' ? 'checkerboard__item-finishing_finishing_additional' : ''}`}>
                                              {subsection.finishing}
                                            </div>
                                            <div className="checkerboard__item-area" title={`${subsection.area} м²`}>
                                              {subsection.area} м²
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                    )
                                  })}
                                  {/* Пустые ячейки для выравнивания */}
                                  {Array.from({ length: 3 }).map((_, idx) => (
                                    <div key={`empty-${idx}`} className="checkerboard__item checkerboard__item_empty checkerboard__item_loading"></div>
                                  ))}
                                </div>
                              ))}
                              <div className="checkerboard__apartments-col--last"></div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default ApartmentsCheckerboard
