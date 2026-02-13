import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import SearchFilters from '../components/SearchFilters'
import ObjectCard from '../components/ObjectCard'
import ObjectTypeFilter from '../components/ObjectTypeFilter'
import '../pages/ObjectsList.css'

const ObjectsList = () => {
  const navigate = useNavigate()
  const [authData, setAuthData] = useState(null)
  const [selectedObjectType, setSelectedObjectType] = useState('apartments')
  const [viewType, setViewType] = useState('list') // 'list', 'table', 'plans', 'map'
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
  }, [selectedObjectType, viewType, filters, pagination.offset, authData])

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

      // ВАЖНО: Принудительно загружаем БЛОКИ (комплексы) для главной страницы
      // Если selectedObjectType === 'apartments' и viewType === 'list', это означает КОМПЛЕКСЫ
      const shouldLoadBlocks = selectedObjectType === 'apartments' && viewType === 'list'
      
      console.log('DEBUG loadObjects:', {
        selectedObjectType,
        viewType,
        shouldLoadBlocks,
        condition: `${selectedObjectType} === 'apartments' && ${viewType} === 'list'`
      })

      let response
      if (shouldLoadBlocks) {
        // Загружаем комплексы (блоки) вместо квартир
        // Явно передаем 'blocks' чтобы контроллер использовал getBlocksSearch
        const blocksParams = { ...params, object_type: 'blocks' }
        console.log('DEBUG: Загружаем БЛОКИ (комплексы) с параметрами:', blocksParams)
        response = await trendAgentAPI.getObjectsList('blocks', blocksParams)
        console.log('DEBUG: Загружены блоки (комплексы):', {
          success: response.success,
          dataKeys: response.data ? Object.keys(response.data) : [],
          objectsLength: response.data?.objects?.length || 0,
          dataLength: Array.isArray(response.data?.data) ? response.data.data.length : 0,
          blocksCount: response.data?.blocks_count || response.blocks_count,
          firstObject: response.data?.objects?.[0] || (Array.isArray(response.data?.data) ? response.data.data[0] : null),
          fullResponse: response
        })
      } else if (selectedObjectType === 'apartments') {
        console.log('DEBUG: Загружаем КВАРТИРЫ (не блоки!)')
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
        // Для блоков (комплексов) данные находятся в response.data.objects
        let objectsList = []
        if (selectedObjectType === 'apartments' && viewType === 'list') {
          // Для блоков структура из getObjectsList: response.data.objects (из TrendSsoController)
          // Контроллер оборачивает данные в { data: { objects: [...], blocks_count: ... } }
          objectsList = response.data?.objects || []
          
          if (objectsList.length === 0) {
            // Fallback: возможно данные в другом формате
            objectsList = response.data?.data || (Array.isArray(response.data) ? response.data : [])
          }
          
          console.log('DEBUG: Извлеченные блоки:', {
            count: objectsList.length,
            fromObjects: response.data?.objects?.length || 0,
            fromData: Array.isArray(response.data?.data) ? response.data.data.length : 0,
            firstBlock: objectsList[0] ? {
              id: objectsList[0]._id || objectsList[0].id,
              name: objectsList[0].name,
              hasImage: !!(objectsList[0].image || objectsList[0].images)
            } : null
          })
        } else {
          objectsList = response.data?.objects || response.data?.data || response.data || []
        }
        
        setObjects(objectsList)
        
        // Для блоков total_count может быть в blocks_count или total
        let total = response.total_count || response.total
        if (selectedObjectType === 'apartments' && viewType === 'list') {
          // Для блоков используем blocks_count из response.data или response
          total = response.data?.blocks_count || response.blocks_count || response.total_count || response.total || objectsList.length
        }
        setTotalCount(total || objectsList.length)
        
        setPagination(prev => ({
          ...prev,
          hasMore: response.pagination?.has_more || 
                   (pagination.offset + objectsList.length < (total || 0)),
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
    // Если это комплексы (блоки), переходим на страницу apartments (так как детальная страница блока использует /apartments/:id)
    const targetType = (selectedObjectType === 'apartments' && viewType === 'list') ? 'apartments' : selectedObjectType
    navigate(`/${targetType}/${objectId}${objectGuid ? `?guid=${objectGuid}` : ''}`)
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
        <p className="text-muted-foreground">
          {selectedObjectType === 'apartments' && viewType === 'list' 
            ? 'Комплексы недвижимости TrendAgent' 
            : 'Поиск и просмотр объектов недвижимости TrendAgent'}
        </p>
      </div>

      <ObjectTypeFilter
        selectedType={selectedObjectType}
        onChange={handleObjectTypeChange}
      />

      {/* Вкладки для объектов (Комплексы/Квартиры/Планировки/На карте) */}
      {selectedObjectType === 'apartments' && (
        <div className="objects-view-tabs">
          <button
            className={`view-tab ${viewType === 'list' ? 'active' : ''}`}
            onClick={() => {
              setViewType('list')
              setPagination(prev => ({ ...prev, offset: 0, page: 1 }))
            }}
          >
            Комплексы
          </button>
          <button
            className="view-tab"
            onClick={() => navigate('/objects/table')}
          >
            Квартиры
          </button>
          <button
            className="view-tab"
            onClick={() => navigate('/objects/plans')}
          >
            Планировки
          </button>
          <button
            className="view-tab"
            onClick={() => navigate('/objects/map')}
          >
            На карте
          </button>
        </div>
      )}

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
            <p className="ml-4">
              {selectedObjectType === 'apartments' && viewType === 'list' 
                ? 'Загрузка комплексов...' 
                : 'Загрузка объектов...'}
            </p>
          </div>
        </div>
      ) : (
        <>
          <div className="mb-4 text-sm text-muted-foreground">
            {selectedObjectType === 'apartments' && viewType === 'list' 
              ? `Найдено комплексов: ${totalCount}`
              : `Найдено объектов: ${totalCount}`}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
            {objects.map((object) => (
              <ObjectCard
                key={object._id || object.guid || object.id}
                object={object}
                objectType={(selectedObjectType === 'apartments' && viewType === 'list') ? 'blocks' : selectedObjectType}
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
