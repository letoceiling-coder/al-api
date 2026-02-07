import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import ObjectCard from '../components/ObjectCard'
import SearchFilters from '../components/SearchFilters'
import './VillagesPlots.css'

const VillagesPlots = () => {
  const navigate = useNavigate()
  const [authData, setAuthData] = useState(null)
  const [plots, setPlots] = useState([])
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
      loadPlots()
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

  const loadPlots = async () => {
    if (!authData || !authData.authenticated) return

    setLoading(true)
    try {
      const params = {
        phone: filters.phone,
        password: filters.password,
        object_type: 'plots',
        count: pagination.count,
        offset: pagination.offset,
        ...filters,
      }

      const response = await trendAgentAPI.getObjectsList('plots', params)

      if (response.success) {
        const plotsList = response.data?.objects || response.data?.data || []
        setPlots(plotsList)
        setTotalCount(response.total_count || plotsList.length)
        setPagination(prev => ({
          ...prev,
          hasMore: response.pagination?.has_more || 
                   (pagination.offset + plotsList.length < (response.total_count || 0)),
        }))
      }
    } catch (error) {
      console.error('Ошибка загрузки участков:', error)
      setPlots([])
    } finally {
      setLoading(false)
    }
  }

  const handlePageChange = (newPage) => {
    const newOffset = (newPage - 1) * pagination.count
    setPagination(prev => ({ ...prev, page: newPage, offset: newOffset }))
  }

  const handlePlotClick = (plotId, plotGuid) => {
    // Для участков пока используем общую страницу поселка
    navigate(`/plots/${plotId}${plotGuid ? `?guid=${plotGuid}` : ''}`)
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
        <h1>Участки</h1>
      </div>

      <div className="villages-tabs">
        <button className="tab" onClick={() => navigate('/villages/list')}>
          Посёлки
        </button>
        <button className="tab active">Участки</button>
        <button className="tab" onClick={() => navigate('/villages/map')}>
          На карте
        </button>
      </div>

      <SearchFilters
        objectType="plots"
        filters={filters}
        onFilterChange={setFilters}
        onSearch={loadPlots}
      />

      {loading ? (
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка участков...</p>
        </div>
      ) : (
        <>
          <div className="villages-info">
            Найдено участков: {totalCount}
          </div>

          <div className="villages-grid">
            {plots.map((plot) => (
              <ObjectCard
                key={plot._id || plot.guid || plot.id}
                object={plot}
                objectType="plots"
                onClick={() => handlePlotClick(
                  plot._id || plot.id,
                  plot.guid
                )}
              />
            ))}
          </div>

          {plots.length === 0 && !loading && (
            <div className="empty-state">
              <p>Участки не найдены</p>
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

export default VillagesPlots
