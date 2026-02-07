import { useState, useEffect } from 'react'
import { useParams, useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import { getImageUrl } from '../utils/imageUtils'
import './HousesCheckerboard.css'

const ApartmentsCheckerboard = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const guid = searchParams.get('guid')

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [buildings, setBuildings] = useState([])
  const [selectedBuilding, setSelectedBuilding] = useState(null)
  const [apartments, setApartments] = useState(null)
  const [phone, setPhone] = useState('+79045393434')
  const [password, setPassword] = useState('nwBvh4q')

  useEffect(() => {
    loadBuildings()
  }, [id])

  useEffect(() => {
    if (selectedBuilding) {
      loadApartments()
    }
  }, [selectedBuilding, id])

  const loadBuildings = async () => {
    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
        // Для квартир можно передать room параметры из query
        room: searchParams.getAll('room').length > 0 
          ? searchParams.getAll('room').map(r => parseInt(r))
          : undefined,
      }

      const response = await trendAgentAPI.getApartmentsCheckerboardBuildings(id, params)

      console.log('Checkerboard buildings response:', response)

      if (response.success) {
        // Обрабатываем различные структуры ответа
        let buildingsData = []
        
        if (Array.isArray(response.data)) {
          buildingsData = response.data
        } else if (response.data && typeof response.data === 'object') {
          // Проверяем results (основной массив корпусов)
          if (Array.isArray(response.data.results)) {
            buildingsData = response.data.results
          } else if (Array.isArray(response.data.buildings)) {
            buildingsData = response.data.buildings
          } else if (Array.isArray(response.data.data)) {
            buildingsData = response.data.data
          } else if (response.data.id || response.data._id || response.data.building_id) {
            // Один корпус в виде объекта
            buildingsData = [response.data]
          }
        }
        
        console.log('Parsed buildings data:', buildingsData)
        setBuildings(buildingsData)
        
        // Автоматически выбираем первый корпус, если есть
        if (buildingsData.length > 0 && !selectedBuilding) {
          const firstBuildingId = buildingsData[0].id || buildingsData[0]._id || buildingsData[0].building_id
          console.log('Auto-selecting first building:', firstBuildingId)
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

      // Проверяем параметр apartments-onrequest из URL
      if (searchParams.get('apartments-onrequest') === 'true') {
        params.onrequest = true
      }

      const response = await trendAgentAPI.getApartmentsCheckerboardApartments(id, params)

      console.log('Checkerboard apartments response:', response)

      if (response.success) {
        // Обрабатываем различные структуры ответа
        let apartmentsData = null
        
        if (Array.isArray(response.data)) {
          apartmentsData = response.data
        } else if (response.data && typeof response.data === 'object') {
          // Может быть объект с данными по этажам/секциям
          if (Array.isArray(response.data.apartments)) {
            apartmentsData = response.data.apartments
          } else if (Array.isArray(response.data.data)) {
            apartmentsData = response.data.data
          } else if (response.data.floors || response.data.sections) {
            // Данные структурированы по этажам/секциям - нужно распаковать
            apartmentsData = []
            const floors = response.data.floors || []
            const sections = response.data.sections || []
            
            // Собираем все квартиры из этажей
            floors.forEach(floor => {
              if (floor.apartments && Array.isArray(floor.apartments)) {
                apartmentsData.push(...floor.apartments)
              }
            })
            
            // Собираем все квартиры из секций
            sections.forEach(section => {
              if (section.apartments && Array.isArray(section.apartments)) {
                apartmentsData.push(...section.apartments)
              }
            })
          } else {
            // Плоский объект - одна квартира
            apartmentsData = [response.data]
          }
        }
        
        console.log('Parsed apartments data:', apartmentsData)
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
    if (!price || price === 0) return '—'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  if (loading && !apartments) {
    return (
      <div className="checkerboard-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="checkerboard-container">
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
    <div className="checkerboard-container">
      <div className="checkerboard-header">
        <button className="btn-back" onClick={() => navigate(-1)}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        <h1>Квартиры на шахматке</h1>
      </div>

      {/* Выбор корпуса */}
      {buildings.length > 0 && (
        <div className="buildings-selector">
          <label>Выберите корпус:</label>
          <select
            value={selectedBuilding || ''}
            onChange={(e) => setSelectedBuilding(e.target.value)}
            className="building-select"
          >
            <option value="">Выберите корпус</option>
            {buildings.map((building) => {
              const buildingId = building.id || building._id || building.building_id
              const buildingName = building.name || building.building_name || `Корпус ${buildingId}`
              const apartmentsCount = building.apartments_count || building.count || 0
              
              return (
                <option key={buildingId} value={buildingId}>
                  {buildingName} ({apartmentsCount} {apartmentsCount === 1 ? 'квартира' : apartmentsCount < 5 ? 'квартиры' : 'квартир'})
                </option>
              )
            })}
          </select>
        </div>
      )}

      {/* Сетка квартир (шахматка) */}
      {selectedBuilding && apartments && (
        <div className="checkerboard-content">
          {(() => {
            // Обрабатываем данные - могут быть массивом или объектом с секциями/этажами
            let apartmentsList = []
            
            if (Array.isArray(apartments)) {
              apartmentsList = apartments
            } else if (apartments && typeof apartments === 'object') {
              // Данные могут быть структурированы по секциям или этажам
              if (Array.isArray(apartments.sections)) {
                apartments.sections.forEach(section => {
                  if (Array.isArray(section.apartments)) {
                    apartmentsList.push(...section.apartments)
                  }
                })
              } else if (Array.isArray(apartments.floors)) {
                apartments.floors.forEach(floor => {
                  if (Array.isArray(floor.apartments)) {
                    apartmentsList.push(...floor.apartments)
                  }
                })
              } else if (Array.isArray(apartments.data)) {
                apartmentsList = apartments.data
              }
            }
            
            if (apartmentsList.length === 0) {
              return (
                <div className="empty-state">
                  <p>Квартиры не найдены</p>
                </div>
              )
            }
            
            // Группируем квартиры по секциям
            const sectionsMap = new Map()
            apartmentsList.forEach(apt => {
              const sectionName = apt.section_name || apt.section || 'Без секции'
              const sectionKey = `${sectionName}_${apt.deadline || ''}`
              
              if (!sectionsMap.has(sectionKey)) {
                sectionsMap.set(sectionKey, {
                  name: sectionName,
                  deadline: apt.deadline || '—',
                  apartments: []
                })
              }
              
              sectionsMap.get(sectionKey).apartments.push(apt)
            })
            
            const sections = Array.from(sectionsMap.values())
            
            return (
              <div className="checkerboard-sections">
                {sections.map((section, sectionIdx) => {
                  // Группируем квартиры по этажам
                  const floorsMap = new Map()
                  section.apartments.forEach(apt => {
                    const floor = apt.floor || 0
                    if (!floorsMap.has(floor)) {
                      floorsMap.set(floor, [])
                    }
                    floorsMap.get(floor).push(apt)
                  })
                  
                  const floors = Array.from(floorsMap.entries())
                    .sort(([a], [b]) => b - a) // Сортируем этажи по убыванию
                  
                  // Группируем квартиры по типам (комнаты + отделка)
                  const getApartmentKey = (apt) => {
                    const rooms = apt.rooms || apt.room || '—'
                    const finishing = apt.finishing_name || apt.finishing || 'Без отделки'
                    const area = apt.privArea || apt.area || apt.area_total || 0
                    return `${rooms}-к.кв_${finishing}_${area}`
                  }
                  
                  // Собираем все уникальные комбинации типов квартир
                  const apartmentTypes = new Map()
                  section.apartments.forEach(apt => {
                    const key = getApartmentKey(apt)
                    if (!apartmentTypes.has(key)) {
                      const rooms = apt.rooms || apt.room || '—'
                      const finishing = apt.finishing_name || apt.finishing || 'Без отделки'
                      const area = apt.privArea || apt.area || apt.area_total || 0
                      apartmentTypes.set(key, {
                        rooms,
                        finishing,
                        area,
                        key
                      })
                    }
                  })
                  
                  const types = Array.from(apartmentTypes.values())
                  
                  return (
                    <div key={sectionIdx} className="checkerboard-section">
                      <div className="section-header">
                        <h2>секция {section.name} - {section.deadline}</h2>
                      </div>
                      
                      <div className="checkerboard-table-wrapper">
                        <table className="checkerboard-table">
                          <thead>
                            <tr>
                              <th className="floor-header">Этаж</th>
                              {types.map((type, typeIdx) => (
                                <th key={typeIdx} className="type-header">
                                  <div className="type-name">
                                    {type.rooms === '—' ? '' : `${type.rooms}-к.кв`}
                                  </div>
                                  <div className="type-details">
                                    {type.finishing} {type.area > 0 ? `${type.area} м²` : ''}
                                  </div>
                                </th>
                              ))}
                            </tr>
                          </thead>
                          <tbody>
                            {floors.map(([floor, floorApartments]) => (
                              <tr key={floor}>
                                <td className="floor-cell">{floor}</td>
                                {types.map((type, typeIdx) => {
                                  // Находим квартиру этого типа на этом этаже
                                  const apartment = floorApartments.find(apt => getApartmentKey(apt) === type.key)
                                  
                                  if (!apartment) {
                                    return <td key={typeIdx} className="apartment-cell empty"></td>
                                  }
                                  
                                  const number = apartment.number || apartment.apartment_number || '—'
                                  const price = apartment.price || apartment.base_price || null
                                  const status = apartment.status?.name || apartment.status || apartment.booking_status || 'Свободная'
                                  const isSold = status.toLowerCase().includes('продан') || status.toLowerCase().includes('sold')
                                  const isBooked = status.toLowerCase().includes('забронирован') || status.toLowerCase().includes('booked')
                                  const isAvailable = !isSold && !isBooked
                                  
                                  return (
                                    <td 
                                      key={typeIdx} 
                                      className={`apartment-cell ${isSold ? 'sold' : isBooked ? 'booked' : 'available'}`}
                                    >
                                      <div className="apartment-cell-content">
                                        <div className="apartment-type">{type.rooms === '—' ? '' : `${type.rooms}-к.кв`}</div>
                                        <div className="apartment-number">№ {number}</div>
                                        {price && (
                                          <div className="apartment-price">{formatPrice(price)}</div>
                                        )}
                                        <div className={`apartment-status ${isSold ? 'sold' : isBooked ? 'booked' : 'available'}`}>
                                          {status}
                                        </div>
                                        <div className="apartment-details">
                                          {type.finishing} {type.area > 0 ? `${type.area} м²` : ''}
                                        </div>
                                      </div>
                                    </td>
                                  )
                                })}
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  )
                })}
              </div>
            )
          })()}
        </div>
      )}

      {!selectedBuilding && (
        <div className="empty-state">
          <p>Выберите корпус для отображения квартир</p>
        </div>
      )}
    </div>
  )
}

export default ApartmentsCheckerboard
