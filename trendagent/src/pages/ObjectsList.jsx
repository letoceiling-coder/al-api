import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import trendAgentAPI from '../services/api'
import SearchFilters from '../components/SearchFilters'
import ObjectCard from '../components/ObjectCard'
import ObjectTypeFilter from '../components/ObjectTypeFilter'
import '../pages/ObjectsList.css'

const ObjectsList = () => {
  const navigate = useNavigate()
  const [authData, setAuthData] = useState(null)
  const [selectedObjectType, setSelectedObjectType] = useState('apartments')
  const [objects, setObjects] = useState([])
  const [loading, setLoading] = useState(false)
  const [totalCount, setTotalCount] = useState(0)
  const [pagination, setPagination] = useState({
    offset: 0,
    count: 20,
    page: 1,
    hasMore: false,
  })
  const [filters, setFilters] = useState({
    city: '58c665588b6aa52311afa01b', // Санкт-Петербург по умолчанию
    phone: '+79045393434',
    password: 'nwBvh4q',
  })

  // Авторизация при загрузке
  useEffect(() => {
    authenticate()
  }, [])

  // Загрузка объектов при изменении фильтров или типа объекта
  useEffect(() => {
    if (authData && authData.authenticated) {
      loadObjects()
    }
  }, [selectedObjectType, filters, pagination.offset, authData])

  const authenticate = async () => {
    try {
      const response = await trendAgentAPI.authenticate(
        filters.phone,
        filters.password
      )
      if (response.success && response.data.authenticated) {
        setAuthData(response.data)
        // Сохраняем токен в localStorage
        if (response.data.auth_token) {
          localStorage.setItem('trendagent_auth_token', response.data.auth_token)
        }
      }
    } catch (error) {
      console.error('Ошибка авторизации:', error)
      alert('Ошибка авторизации. Проверьте телефон и пароль.')
    }
  }

  const loadObjects = async () => {
    if (!authData || !authData.authenticated) {
      return
    }

    setLoading(true)
    try {
      const params = {
        phone: filters.phone,
        password: filters.password,
        city: filters.city,
        count: pagination.count,
        offset: pagination.offset,
        ...filters,
      }

      let response
      if (selectedObjectType === 'apartments') {
        response = await trendAgentAPI.getApartments(params)
      } else if (selectedObjectType === 'parkings') {
        response = await trendAgentAPI.getParkings(params)
      } else if (selectedObjectType === 'houses') {
        response = await trendAgentAPI.getHouses(params)
      } else if (selectedObjectType === 'plots') {
        response = await trendAgentAPI.getPlots(params)
      } else if (selectedObjectType === 'commercial') {
        response = await trendAgentAPI.getCommercial(params)
      } else {
        response = await trendAgentAPI.getObjectsList(selectedObjectType, params)
      }

      if (response.success) {
        const objectsList = response.data?.objects || response.data?.data || []
        setObjects(objectsList)
        setTotalCount(response.total_count || objectsList.length)
        setPagination(prev => ({
          ...prev,
          hasMore: response.pagination?.has_more || 
                   (pagination.offset + objectsList.length < (response.total_count || 0)),
        }))
      }
    } catch (error) {
      console.error('Ошибка загрузки объектов:', error)
      setObjects([])
      setTotalCount(0)
    } finally {
      setLoading(false)
    }
  }

  const handleFilterChange = (newFilters) => {
    setFilters(prev => ({ ...prev, ...newFilters }))
    setPagination(prev => ({ ...prev, offset: 0, page: 1 }))
  }

  const handleObjectTypeChange = (type) => {
    setSelectedObjectType(type)
    setObjects([])
    setPagination(prev => ({ ...prev, offset: 0, page: 1 }))
  }

  const handlePageChange = (newPage) => {
    const newOffset = (newPage - 1) * pagination.count
    setPagination(prev => ({ ...prev, page: newPage, offset: newOffset }))
  }

  const handleObjectClick = (objectId, objectGuid) => {
    navigate(`/${selectedObjectType}/${objectId}${objectGuid ? `?guid=${objectGuid}` : ''}`)
  }

  if (!authData || !authData.authenticated) {
    return (
      <div className="container mx-auto p-6">
        <div className="card text-center">
          <div className="loading">
            <div className="spinner"></div>
            <p className="ml-4">Авторизация...</p>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="container mx-auto p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold mb-2">PARSER</h1>
        <p className="text-muted-foreground">Поиск и просмотр объектов недвижимости TrendAgent</p>
      </div>

      <ObjectTypeFilter
        selectedType={selectedObjectType}
        onChange={handleObjectTypeChange}
      />

      <SearchFilters
        objectType={selectedObjectType}
        filters={filters}
        onFilterChange={handleFilterChange}
        onSearch={loadObjects}
      />

      {loading ? (
        <div className="card text-center">
          <div className="loading">
            <div className="spinner"></div>
            <p className="ml-4">Загрузка объектов...</p>
          </div>
        </div>
      ) : (
        <>
          <div className="mb-4 text-sm text-muted-foreground">
            Найдено объектов: {totalCount}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            {objects.map((object) => (
              <ObjectCard
                key={object._id || object.guid || object.id}
                object={object}
                objectType={selectedObjectType}
                onClick={() => handleObjectClick(
                  object._id || object.id,
                  object.guid
                )}
              />
            ))}
          </div>

          {objects.length === 0 && !loading && (
            <div className="card text-center py-12">
              <p className="text-muted-foreground">Объекты не найдены</p>
            </div>
          )}

          {/* Пагинация */}
          {totalCount > 0 && (
            <div className="flex justify-center items-center gap-4 mt-6">
              <button
                className="btn btn-secondary"
                disabled={pagination.page === 1}
                onClick={() => handlePageChange(pagination.page - 1)}
              >
                Назад
              </button>
              <span className="text-sm text-muted-foreground">
                Страница {pagination.page} из {Math.ceil(totalCount / pagination.count)}
              </span>
              <button
                className="btn btn-secondary"
                disabled={!pagination.hasMore}
                onClick={() => handlePageChange(pagination.page + 1)}
              >
                Вперед
              </button>
            </div>
          )}
        </>
      )}
    </div>
  )
}

export default ObjectsList
