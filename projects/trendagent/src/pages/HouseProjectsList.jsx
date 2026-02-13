import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import ObjectCard from '../components/ObjectCard'
import SearchFilters from '../components/SearchFilters'
import './VillagesList.css'

const HouseProjectsList = () => {
  const navigate = useNavigate()
  const [authData, setAuthData] = useState(null)
  const [projects, setProjects] = useState([])
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
      loadProjects()
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

  const loadProjects = async () => {
    if (!authData || !authData.authenticated) return

    setLoading(true)
    try {
      const params = {
        phone: filters.phone,
        password: filters.password,
        object_type: 'contractors',
        count: pagination.count,
        offset: pagination.offset,
        ...filters,
      }

      const response = await trendAgentAPI.getHouseProjects(params)

      if (response.success) {
        const projectsList = response.data?.objects || response.data?.data || []
        setProjects(projectsList)
        setTotalCount(response.total_count || projectsList.length)
        setPagination(prev => ({
          ...prev,
          hasMore: response.pagination?.has_more || 
                   (pagination.offset + projectsList.length < (response.total_count || 0)),
        }))
      }
    } catch (error) {
      console.error('Ошибка загрузки проектов:', error)
      setProjects([])
    } finally {
      setLoading(false)
    }
  }

  const handlePageChange = (newPage) => {
    const newOffset = (newPage - 1) * pagination.count
    setPagination(prev => ({ ...prev, page: newPage, offset: newOffset }))
  }

  const handleProjectClick = (projectId, projectGuid) => {
    navigate(`/houseproject/${projectGuid || projectId}`)
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
        <button type="button" className="btn btn-outline btn-back" onClick={() => navigate('/')}>
          <svg className="w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
          </svg>
          Назад
        </button>
        <h1>Проекты домов</h1>
      </div>

      <SearchFilters
        objectType="contractors"
        filters={filters}
        onFilterChange={setFilters}
        onSearch={loadProjects}
      />

      {loading ? (
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка проектов...</p>
        </div>
      ) : (
        <>
          <div className="villages-info">
            Найдено проектов: {totalCount}
          </div>

          <div className="villages-grid">
            {projects.map((project) => (
              <ObjectCard
                key={project._id || project.guid || project.id}
                object={project}
                objectType="contractors"
                onClick={() => handleProjectClick(
                  project._id || project.id,
                  project.guid
                )}
              />
            ))}
          </div>

          {projects.length === 0 && !loading && (
            <div className="empty-state">
              <p>Проекты не найдены</p>
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

export default HouseProjectsList
