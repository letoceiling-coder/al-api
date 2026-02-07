import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import ObjectCard from '../components/ObjectCard'
import SearchFilters from '../components/SearchFilters'
import './VillagesList.css'

const VillagesList = () => {
  const navigate = useNavigate()
  const [authData, setAuthData] = useState(null)
  const [villages, setVillages] = useState([])
  const [loading, setLoading] = useState(false)
  const [totalCount, setTotalCount] = useState(0)
  const [pagination, setPagination] = useState({
    offset: 0,
    count: 20,
    page: 1,
    hasMore: false,
  })
  const [filters, setFilters] = useState({
    city: '58c665588b6aa52311afa01b',
    phone: '+79045393434',
    password: 'nwBvh4q',
  })

  useEffect(() => {
    authenticate()
  }, [])

  useEffect(() => {
    if (authData && authData.authenticated) {
      loadVillages()
    }
  }, [authData, pagination.offset, filters])

  const authenticate = async () => {
    try {
      const response = await trendAgentAPI.authenticate(
        filters.phone,
        filters.password
      )
      if (response.success && response.data.authenticated) {
        setAuthData(response.data)
      }
    } catch (error) {
      console.error('Ошибка авторизации:', error)
    }
  }

  const loadVillages = async () => {
    if (!authData || !authData.authenticated) return

    setLoading(true)
    try {
      const params = {
        phone: filters.phone,
        password: filters.password,
        object_type: 'villages',
        count: pagination.count,
        offset: pagination.offset,
        ...filters,
      }

      const response = await trendAgentAPI.getObjectsList('villages', params)

      if (response.success) {
        const villagesList = response.data?.objects || response.data?.data || []
        setVillages(villagesList)
        setTotalCount(response.total_count || villagesList.length)
        setPagination(prev => ({
          ...prev,
          hasMore: response.pagination?.has_more || 
                   (pagination.offset + villagesList.length < (response.total_count || 0)),
        }))
      }
    } catch (error) {
      console.error('Ошибка загрузки поселков:', error)
      setVillages([])
    } finally {
      setLoading(false)
    }
  }

  const handlePageChange = (newPage) => {
    const newOffset = (newPage - 1) * pagination.count
    setPagination(prev => ({ ...prev, page: newPage, offset: newOffset }))
  }

  const handleVillageClick = (villageId, villageGuid) => {
    navigate(`/plots/${villageId}${villageGuid ? `?guid=${villageGuid}` : ''}`)
  }

  if (!authData || !authData.authenticated) {
    return (
      <div className="villages-list-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Авторизация...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="villages-list-container">
      <div className="villages-list-header">
        <button className="btn-back" onClick={() => navigate('/')}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        <h1>Посёлки</h1>
      </div>

      <div className="villages-tabs">
        <button className="tab active">Посёлки</button>
        <button className="tab" onClick={() => navigate('/villages/plots')}>
          Участки
        </button>
        <button className="tab" onClick={() => navigate('/villages/map')}>
          На карте
        </button>
      </div>

      <SearchFilters
        objectType="plots"
        filters={filters}
        onFilterChange={setFilters}
        onSearch={loadVillages}
      />

      {loading ? (
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка поселков...</p>
        </div>
      ) : (
        <>
          <div className="villages-info">
            Найдено поселков: {totalCount}
          </div>

          <div className="villages-grid">
            {villages.map((village) => (
              <ObjectCard
                key={village._id || village.guid || village.id}
                object={village}
                objectType="plots"
                onClick={() => handleVillageClick(
                  village._id || village.id,
                  village.guid
                )}
              />
            ))}
          </div>

          {villages.length === 0 && !loading && (
            <div className="empty-state">
              <p>Посёлки не найдены</p>
            </div>
          )}

          {totalCount > 0 && (
            <div className="pagination">
              <button
                className="btn btn-secondary"
                disabled={pagination.page === 1}
                onClick={() => handlePageChange(pagination.page - 1)}
              >
                Назад
              </button>
              <span className="pagination-info">
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

export default VillagesList
