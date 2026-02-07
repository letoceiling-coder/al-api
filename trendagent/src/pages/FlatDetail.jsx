import { useState, useEffect, useCallback } from 'react'
import { useParams, useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import { getImageUrl, getImageUrlFull, getImageUrls } from '../utils/imageUtils'
import FlatRewardCard from '../components/detail/FlatRewardCard'
import FlatHighlights from '../components/detail/FlatHighlights'
import FlatPassport from '../components/detail/FlatPassport'
import FlatBlockInfo from '../components/detail/FlatBlockInfo'
import './FlatDetail.css'

const FlatDetail = () => {
  const { id, apartmentId } = useParams()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const blockId = searchParams.get('block') || id

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [apartmentData, setApartmentData] = useState(null)
  const [blockData, setBlockData] = useState(null)
  const [rewardsData, setRewardsData] = useState(null)
  const [discountsData, setDiscountsData] = useState(null)
  const [mortgageData, setMortgageData] = useState(null)
  const [installmentsData, setInstallmentsData] = useState(null)
  const [plansData, setPlansData] = useState(null)
  const [finishingsData, setFinishingsData] = useState(null)
  const [phone, setPhone] = useState('+79045393434')
  const [password, setPassword] = useState('nwBvh4q')

  // Слайдер фото (как у донора): один главный слайд, счётчик, миниатюры
  const [currentImageIndex, setCurrentImageIndex] = useState(0)
  const [imageType, setImageType] = useState('plans') // 'plans' | 'finishing'

  // Поэтажный план (интерактивный режим как у донора)
  const [floorPlanDirectory, setFloorPlanDirectory] = useState(null)
  const [floorPlanDirectoryLoading, setFloorPlanDirectoryLoading] = useState(false)
  const [floorPlanData, setFloorPlanData] = useState(null)
  const [floorPlanLoading, setFloorPlanLoading] = useState(false)
  const [selectedBuilding, setSelectedBuilding] = useState(null)
  const [selectedSection, setSelectedSection] = useState(null)
  const [selectedFloor, setSelectedFloor] = useState(null)
  const [floorPlanZoom, setFloorPlanZoom] = useState(1)
  const [floorPlanFullscreen, setFloorPlanFullscreen] = useState(false)
  const [floorPlanRotation, setFloorPlanRotation] = useState(0)
  const [showFloorPlanInteractive, setShowFloorPlanInteractive] = useState(false)

  useEffect(() => {
    loadFlatDetail()
  }, [blockId, apartmentId])

  useEffect(() => {
    if (apartmentData) {
      setCurrentImageIndex(0)
    }
  }, [imageType, apartmentData])

  // При переключении на таб "Поэтажный план" открываем интерактивный режим, если доступен
  useEffect(() => {
    if (imageType === 'plans' && hasFloorPlanInteractive && !showFloorPlanInteractive) {
      // Автоматически открываем интерактивный режим при переключении на таб "Поэтажный план"
      // Но только если есть данные для автозаполнения или справочник загружен
      if (floorPlanDirectory || (selectedBuilding && selectedSection && selectedFloor != null)) {
        setShowFloorPlanInteractive(true)
      }
    }
  }, [imageType, hasFloorPlanInteractive, floorPlanDirectory, selectedBuilding, selectedSection, selectedFloor])

  // Загрузка справочника поэтажного плана при наличии blockId
  useEffect(() => {
    if (!blockId || !phone || !password) return
    let cancelled = false
    setFloorPlanDirectoryLoading(true)
    trendAgentAPI.getFloorPlanDirectory(blockId, { phone, password })
      .then((res) => {
        if (cancelled) return
        const raw = res.data ?? res
        setFloorPlanDirectory(raw)
        // Выставить начальные значения из квартиры, если есть
        if (apartmentData) {
          const apt = apartmentData.data || apartmentData
          const buildingId = apt.building_id || apt.building?.id || apt.building?._id
          const sectionId = apt.section_id || apt.section?.id || apt.section?._id
          const floorNum = apt.floor ?? apt.floor_number
          if (buildingId) setSelectedBuilding(buildingId)
          if (sectionId) setSelectedSection(sectionId)
          if (floorNum != null) setSelectedFloor(floorNum)
          
          // Если все данные есть и мы на табе "Поэтажный план", автоматически открываем интерактивный режим
          if (buildingId && sectionId && floorNum != null && imageType === 'plans') {
            setShowFloorPlanInteractive(true)
          }
        }
      })
      .catch(() => {
        if (!cancelled) setFloorPlanDirectory(null)
      })
      .finally(() => {
        if (!cancelled) setFloorPlanDirectoryLoading(false)
      })
    return () => { cancelled = true }
  }, [blockId, phone, password])

  // Загрузка плана этажа при смене корпус/секция/этаж
  useEffect(() => {
    if (!selectedBuilding || !selectedSection || selectedFloor == null || selectedFloor === '' || !blockId) {
      setFloorPlanData(null)
      return
    }
    let cancelled = false
    setFloorPlanLoading(true)
    trendAgentAPI.getFloorPlan(blockId, {
      phone,
      password,
      building_id: selectedBuilding,
      section_id: selectedSection,
      floor_number: selectedFloor,
    })
      .then((res) => {
        if (cancelled) return
        setFloorPlanData(res.data ?? res)
      })
      .catch(() => {
        if (!cancelled) setFloorPlanData(null)
      })
      .finally(() => {
        if (!cancelled) setFloorPlanLoading(false)
      })
    return () => { cancelled = true }
  }, [blockId, phone, password, selectedBuilding, selectedSection, selectedFloor])

  const loadFlatDetail = async () => {
    setLoading(true)
    setError(null)
    try {
      const params = { phone, password }
      const response = await trendAgentAPI.getApartmentFlatDetail(blockId, apartmentId, params)
      if (response.success) {
        setApartmentData(response.data?.apartment || response.data)
        setBlockData(response.data?.block || null)
        setRewardsData(response.data?.rewards || null)
        setDiscountsData(response.data?.discounts || null)
        setMortgageData(response.data?.mortgage || null)
        setInstallmentsData(response.data?.installments || null)
        setPlansData(response.data?.plans || null)
        setFinishingsData(response.data?.finishings || null)
      } else {
        setError(response.message || 'Ошибка загрузки данных квартиры')
      }
    } catch (err) {
      console.error('Ошибка загрузки данных квартиры:', err)
      setError(err.message || err?.message || 'Ошибка загрузки данных квартиры')
    } finally {
      setLoading(false)
    }
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

  const getStatus = (apt) => {
    if (!apt) return 'Свободна'
    if (typeof apt.status === 'string') return apt.status
    if (apt.status?.name) return apt.status.name
    if (apt.booking_status) return apt.booking_status
    if (apt.is_booked) return 'Забронирована'
    return 'Свободна'
  }

  const getStatusClass = (status) => {
    const s = String(status).toLowerCase().replace(/\s+/g, '-')
    if (s.includes('свободн') || s.includes('available')) return 'status-available'
    if (s.includes('забронирован') || s.includes('booked')) return 'status-booked'
    if (s.includes('продан') || s.includes('sold')) return 'status-sold'
    return 'status-default'
  }

  // Нормализация справочника поэтажного плана (разные форматы API)
  const getFloorPlanOptions = () => {
    const dir = floorPlanDirectory?.data ?? floorPlanDirectory
    if (!dir || typeof dir !== 'object') return { buildings: [], sections: [], floors: [] }
    const buildings = dir.buildings ?? dir.building_list ?? dir.corpus ?? []
    const sections = dir.sections ?? dir.section_list ?? []
    const floors = dir.floors ?? dir.floor_list ?? []
    return {
      buildings: Array.isArray(buildings) ? buildings : [],
      sections: Array.isArray(sections) ? sections : [],
      floors: Array.isArray(floors) ? floors : [],
    }
  }

  const getFloorPlanImageUrl = () => {
    const data = floorPlanData?.data ?? floorPlanData
    if (!data) return null
    if (typeof data === 'string' && (data.startsWith('http') || data.startsWith('/'))) return data
    const url = data.image_url ?? data.image ?? data.url ?? data.plan_image ?? data.src
    if (url) return getImageUrlFull(url) || getImageUrl(url)
    if (data.svg) return null
    return null
  }

  const getFloorPlanSvg = () => {
    const data = floorPlanData?.data ?? floorPlanData
    if (!data || typeof data !== 'object') return null
    return data.svg ?? null
  }

  const imagesByType = (() => {
    const apt = apartmentData?.data || apartmentData
    const plans = []
    const finishing = []
    const photos = []

    if (apt?.plan) {
      const u = getImageUrlFull(apt.plan) || getImageUrl(apt.plan)
      if (u) plans.push({ url: u, urlFull: u, type: 'plan' })
    }
    if (apt?.plan_image) {
      const u = getImageUrlFull(apt.plan_image) || getImageUrl(apt.plan_image)
      if (u && !plans.find(i => i.url === u)) plans.push({ url: u, urlFull: u, type: 'plan_image' })
    }
    if (Array.isArray(apt?.plans)) {
      apt.plans.forEach(img => {
        const u = getImageUrlFull(img) || getImageUrl(img)
        if (u && !plans.find(i => i.url === u)) plans.push({ url: u, urlFull: u, type: 'plan' })
      })
    }
    if (plansData?.data && Array.isArray(plansData.data)) {
      plansData.data.forEach(plan => {
        const u = getImageUrlFull(plan) || getImageUrl(plan)
        if (u && !plans.find(i => i.url === u)) plans.push({ url: u, urlFull: u, type: 'plan' })
      })
    }

    if (apt?.finishing_image) {
      const u = getImageUrlFull(apt.finishing_image) || getImageUrl(apt.finishing_image)
      if (u) finishing.push({ url: u, urlFull: u, type: 'finishing' })
    }
    if (Array.isArray(apt?.finishing_images)) {
      apt.finishing_images.forEach(img => {
        const u = getImageUrlFull(img) || getImageUrl(img)
        if (u && !finishing.find(i => i.url === u)) finishing.push({ url: u, urlFull: u, type: 'finishing' })
      })
    }
    if (finishingsData?.data && Array.isArray(finishingsData.data)) {
      finishingsData.data.forEach(fin => {
        if (fin.image) {
          const u = getImageUrlFull(fin.image) || getImageUrl(fin.image)
          if (u && !finishing.find(i => i.url === u)) finishing.push({ url: u, urlFull: u, type: 'finishing' })
        }
      })
    }

    if (Array.isArray(apt?.images)) {
      apt.images.forEach(img => {
        const u = getImageUrlFull(img) || getImageUrl(img)
        if (u) photos.push({ url: u, urlFull: u, type: 'photo' })
      })
    }
    if (apt?.image) {
      const u = getImageUrlFull(apt.image) || getImageUrl(apt.image)
      if (u && !photos.find(i => i.url === u)) photos.push({ url: u, urlFull: u, type: 'photo' })
    }
    if (Array.isArray(apt?.renderer)) {
      apt.renderer.forEach(img => {
        const u = getImageUrlFull(img) || getImageUrl(img)
        if (u && !photos.find(i => i.url === u)) photos.push({ url: u, urlFull: u, type: 'renderer' })
      })
    }

    return { plans, finishing, photos }
  })()

  const getCurrentImages = () => {
    if (imageType === 'plans') return imagesByType.plans.length > 0 ? imagesByType.plans : imagesByType.photos
    if (imageType === 'finishing') return imagesByType.finishing.length > 0 ? imagesByType.finishing : imagesByType.photos
    return imagesByType.photos
  }

  const images = getCurrentImages()
  const currentImage = images[currentImageIndex] || null
  const hasGallery = imagesByType.plans.length > 0 || imagesByType.finishing.length > 0 || imagesByType.photos.length > 0
  const fpOptions = getFloorPlanOptions()
  const hasFloorPlanInteractive = fpOptions.buildings.length > 0 && blockId

  const nextImage = () => setCurrentImageIndex((prev) => (prev + 1) % images.length)
  const prevImage = () => setCurrentImageIndex((prev) => (prev - 1 + images.length) % images.length)
  const selectImage = (index) => setCurrentImageIndex(index)

  const apartment = apartmentData?.data || apartmentData
  const block = blockData?.data || blockData
  const number = apartment?.number || apartment?.apartment_number || '—'
  const floor = apartment?.floor ?? '—'
  const section = (apartment?.section_name || apartment?.section) ?? '—'
  const building = (apartment?.building_name || apartment?.building || apartment?.corpus) ?? '—'
  const area = apartment?.privArea ?? apartment?.area ?? apartment?.area_total ?? null
  const kitchenArea = apartment?.kitchenArea ?? apartment?.kitchen_area ?? null
  const livingArea = apartment?.livingArea ?? apartment?.living_area ?? null
  const finishing = apartment?.finishing_name || apartment?.finishing || '—'
  const basePrice = apartment?.base_price ?? null
  const fullPrice = apartment?.price ?? apartment?.full_price ?? null
  const pricePerM2 = area && basePrice ? formatPricePerM2(basePrice, area) : '—'
  const status = getStatus(apartment)
  const rooms = apartment?.rooms ?? apartment?.room ?? null
  const view = apartment?.view_image ?? apartment?.view ?? null
  const exclusive = apartment?.exclusive ?? apartment?.is_exclusive ?? false

  if (loading) {
    return (
      <div className="flat-detail-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flat-detail-container">
        <div className="error-message">
          <p>{error}</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>Назад</button>
        </div>
      </div>
    )
  }

  if (!apartmentData) {
    return (
      <div className="flat-detail-container">
        <div className="error-message">
          <p>Данные квартиры не найдены</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>Назад</button>
        </div>
      </div>
    )
  }

  return (
    <div className="flat-detail-container">
      <div className="breadcrumbs">
        <button className="btn-back" onClick={() => navigate(-1)}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        {block && <span className="breadcrumb-separator">/</span>}
        {block && <span className="breadcrumb-item">{block.name || block.title || 'Объект'}</span>}
        <span className="breadcrumb-separator">/</span>
        <span className="breadcrumb-item active">Квартира {number}</span>
      </div>

      <div className="flat-header">
        <h1>Квартира {number}</h1>
        {block && <p className="block-name">{block.name || block.title}</p>}
      </div>

      <div className="flat-content">
        <div className="flat-main">
          {/* Галерея: слайдер фото + вкладки Поэтажный план | Отделка — как у донора */}
          {(hasGallery || hasFloorPlanInteractive) && (
            <div className="flat-gallery-section">
              <div className="flat-gallery">
                {/* Табы как у донора */}
                <div className="gallery-tabs">
                  <button
                    type="button"
                    className={`btn btn_secondary px-4 gallery-tab ${imageType === 'plans' ? 'active' : ''}`}
                    onClick={() => setImageType('plans')}
                  >
                    <span className="btn__content justify-content-center">Поэтажный план</span>
                  </button>
                  {imagesByType.finishing.length > 0 && (
                    <button
                      type="button"
                      className={`btn btn_secondary px-4 gallery-tab ${imageType === 'finishing' ? 'active' : ''}`}
                      onClick={() => setImageType('finishing')}
                    >
                      <span className="btn__content justify-content-center">Отделка</span>
                    </button>
                  )}
                </div>

                {/* Кнопка открытия интерактивного поэтажного плана */}
                {imageType === 'plans' && hasFloorPlanInteractive && !showFloorPlanInteractive && (
                  <div className="floor-plan-button-wrapper">
                    <button
                      type="button"
                      className="btn btn_secondary px-4"
                      onClick={() => setShowFloorPlanInteractive(true)}
                    >
                      <span className="btn__content justify-content-center">Поэтажный план</span>
                    </button>
                  </div>
                )}

                {/* Режим интерактивного поэтажного плана (корпус/секция/этаж) */}
                {imageType === 'plans' && hasFloorPlanInteractive && showFloorPlanInteractive && (
                  <div className="floor-plan-interactive">
                    <div className="floor-plan-dropdowns">
                      <div className="floor-plan-select-wrap">
                        <label>Корпус</label>
                        <select
                          value={selectedBuilding || ''}
                          onChange={(e) => {
                            setSelectedBuilding(e.target.value || null)
                            setFloorPlanData(null)
                          }}
                          className="floor-plan-select"
                        >
                          <option value="">—</option>
                          {fpOptions.buildings.map((b) => (
                            <option key={b.id || b._id || b.value} value={b.id || b._id || b.value}>
                              {b.name || b.title || b.label || `Корпус ${b.id || b._id}`}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="floor-plan-select-wrap">
                        <label>Секция</label>
                        <select
                          value={selectedSection || ''}
                          onChange={(e) => {
                            setSelectedSection(e.target.value || null)
                            setFloorPlanData(null)
                          }}
                          className="floor-plan-select"
                        >
                          <option value="">—</option>
                          {fpOptions.sections.map((s) => (
                            <option key={s.id || s._id || s.value} value={s.id || s._id || s.value}>
                              {s.name || s.title || s.label || `Секция ${s.id || s._id}`}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="floor-plan-select-wrap">
                        <label>Этаж</label>
                        <select
                          value={selectedFloor ?? ''}
                          onChange={(e) => {
                            setSelectedFloor(e.target.value === '' ? null : e.target.value)
                            setFloorPlanData(null)
                          }}
                          className="floor-plan-select"
                        >
                          <option value="">—</option>
                          {fpOptions.floors.map((f) => (
                            <option key={f.id ?? f._id ?? f.value ?? f} value={f.id ?? f._id ?? f.value ?? f}>
                              {f.name ?? f.title ?? f.label ?? `Этаж ${f.id ?? f._id ?? f}`}
                            </option>
                          ))}
                        </select>
                      </div>
                    </div>

                    <div className="floor-plan-toolbar">
                      <div className="floor-plan-toolbar-group">
                        <button
                          type="button"
                          className="floor-plan-zoom-btn"
                          onClick={() => setFloorPlanZoom((z) => Math.min(3, z + 0.25))}
                          aria-label="Увеличить"
                          title="Увеличить"
                        >
                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M10 4V16M4 10H16" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                          </svg>
                        </button>
                        <button
                          type="button"
                          className="floor-plan-zoom-btn"
                          onClick={() => setFloorPlanZoom((z) => Math.max(0.5, z - 0.25))}
                          aria-label="Уменьшить"
                          title="Уменьшить"
                        >
                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M4 10H16" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                          </svg>
                        </button>
                      </div>
                      <div className="floor-plan-toolbar-group">
                        <button
                          type="button"
                          className="floor-plan-rotate-btn"
                          onClick={() => setFloorPlanRotation((r) => (r + 90) % 360)}
                          aria-label="Повернуть"
                          title="Повернуть на 90°"
                        >
                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M15 5L17 3L15 1M3 5L1 3L3 1M5 3C5 6.314 7.686 9 11 9M15 17C15 13.686 12.314 11 9 11M5 17L3 19L5 21M17 17L19 19L17 21" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                          </svg>
                        </button>
                        <button
                          type="button"
                          className="floor-plan-compass-btn"
                          onClick={() => setFloorPlanRotation(0)}
                          aria-label="Выровнять по сторонам света"
                          title="Выровнять по сторонам света"
                        >
                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="10" cy="10" r="8" stroke="currentColor" strokeWidth="1.5"/>
                            <path d="M10 2V6M10 14V18M2 10H6M14 10H18" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/>
                            <path d="M10 6L12 10L10 14L8 10Z" fill="currentColor"/>
                          </svg>
                        </button>
                      </div>
                      <div className="floor-plan-toolbar-group">
                        <button
                          type="button"
                          className="floor-plan-fullscreen-btn"
                          onClick={() => setFloorPlanFullscreen((v) => !v)}
                          aria-label="Полноэкранный режим"
                          title="Полноэкранный режим"
                        >
                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            {floorPlanFullscreen ? (
                              <path d="M6 6L4 4M4 4V8M4 4H8M14 6L16 4M16 4V8M16 4H12M6 14L4 16M4 16V12M4 16H8M14 14L16 16M16 16V12M16 16H12" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            ) : (
                              <path d="M6 4H4C3.44772 4 3 4.44772 3 5V7M17 4H16C15.4477 4 15 4.44772 15 5V7M3 13V15C3 15.5523 3.44772 16 4 16H6M17 13V15C17 15.5523 16.5523 16 16 16H15" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            )}
                          </svg>
                        </button>
                        {getFloorPlanImageUrl() && (
                          <a
                            href={getFloorPlanImageUrl()}
                            download
                            target="_blank"
                            rel="noopener noreferrer"
                            className="floor-plan-download-btn"
                            title="Скачать план"
                          >
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                              <path d="M10 2V12M10 12L6 8M10 12L14 8M3 15V17C3 17.5523 3.44772 18 4 18H16C16.5523 18 17 17.5523 17 17V15" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                          </a>
                        )}
                        <button
                          type="button"
                          className="floor-plan-close-btn"
                          onClick={() => setShowFloorPlanInteractive(false)}
                          aria-label="Закрыть"
                          title="Закрыть"
                        >
                          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                          </svg>
                        </button>
                      </div>
                    </div>

                    <div className={`floor-plan-view ${floorPlanFullscreen ? 'fullscreen' : ''}`}>
                      {floorPlanLoading && (
                        <div className="floor-plan-loading">
                          <div className="spinner"></div>
                          <p>Загрузка плана этажа...</p>
                        </div>
                      )}
                      {!floorPlanLoading && getFloorPlanImageUrl() && (
                        <div 
                          className="floor-plan-image-wrap" 
                          style={{ 
                            transform: `scale(${floorPlanZoom}) rotate(${floorPlanRotation}deg)`,
                            transformOrigin: 'center center'
                          }}
                        >
                          <img
                            src={getFloorPlanImageUrl()}
                            alt="План этажа"
                            className="floor-plan-image"
                            draggable={false}
                          />
                        </div>
                      )}
                      {!floorPlanLoading && getFloorPlanSvg() && (
                        <div
                          className="floor-plan-svg-wrap"
                          style={{ 
                            transform: `scale(${floorPlanZoom}) rotate(${floorPlanRotation}deg)`,
                            transformOrigin: 'center center'
                          }}
                          dangerouslySetInnerHTML={{ __html: getFloorPlanSvg() }}
                        />
                      )}
                      {!floorPlanLoading && !getFloorPlanImageUrl() && !getFloorPlanSvg() && selectedBuilding && selectedSection && selectedFloor != null && (
                        <div className="floor-plan-placeholder">
                          План этажа для выбранных корпуса, секции и этажа недоступен или загружается.
                        </div>
                      )}
                    </div>
                  </div>
                )}

                {/* Слайдер фото (планы/отделка) — как у донора */}
                {images.length > 0 && (
                  imageType === 'finishing' ||
                  (imageType === 'plans' && !hasFloorPlanInteractive) ||
                  (imageType === 'plans' && hasFloorPlanInteractive && (!selectedBuilding || !selectedSection || selectedFloor == null))
                ) && (
                  <>
                    <div className="gallery-main">
                      {currentImage && (
                        <>
                          {images.length > 1 && (
                            <button className="gallery-nav-btn gallery-nav-btn-prev" onClick={prevImage} aria-label="Предыдущее изображение">
                              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M15 18L9 12L15 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                              </svg>
                            </button>
                          )}
                          <img
                            src={currentImage.urlFull || currentImage.url}
                            alt={`Квартира ${number} - ${imageType === 'plans' ? 'план' : 'отделка'} ${currentImageIndex + 1}`}
                            className="gallery-main-image"
                            onError={(e) => { e.target.style.display = 'none' }}
                          />
                          {images.length > 1 && (
                            <button className="gallery-nav-btn gallery-nav-btn-next" onClick={nextImage} aria-label="Следующее изображение">
                              <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M9 18L15 12L9 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                              </svg>
                            </button>
                          )}
                          {images.length > 1 && (
                            <div className="gallery-counter">
                              {currentImageIndex + 1} / {images.length}
                            </div>
                          )}
                        </>
                      )}
                    </div>
                    {images.length > 1 && (
                      <div className="gallery-thumbnails">
                        {images.map((img, index) => (
                          <div
                            key={index}
                            className={`gallery-thumbnail ${index === currentImageIndex ? 'active' : ''}`}
                            onClick={() => selectImage(index)}
                          >
                            <img src={img.url || img.urlFull} alt="" onError={(e) => { e.target.style.display = 'none' }} />
                          </div>
                        ))}
                      </div>
                    )}
                  </>
                )}
              </div>
            </div>
          )}

          <div className="flat-specs-section">
            <h2>Характеристики</h2>
            <div className="specs-grid">
              {rooms && <div className="spec-item"><span className="spec-label">Комнат:</span><span className="spec-value">{rooms === 1 ? 'Студия' : `${rooms}-комн.`}</span></div>}
              {area && <div className="spec-item"><span className="spec-label">Общая площадь:</span><span className="spec-value">{area} м²</span></div>}
              {livingArea && <div className="spec-item"><span className="spec-label">Жилая площадь:</span><span className="spec-value">{livingArea} м²</span></div>}
              {kitchenArea && <div className="spec-item"><span className="spec-label">Площадь кухни:</span><span className="spec-value">{kitchenArea} м²</span></div>}
              <div className="spec-item"><span className="spec-label">Этаж:</span><span className="spec-value">{floor}</span></div>
              {section !== '—' && <div className="spec-item"><span className="spec-label">Секция:</span><span className="spec-value">{section}</span></div>}
              {building !== '—' && <div className="spec-item"><span className="spec-label">Корпус:</span><span className="spec-value">{building}</span></div>}
              <div className="spec-item"><span className="spec-label">Отделка:</span><span className="spec-value">{finishing}</span></div>
              {view && <div className="spec-item"><span className="spec-label">Вид:</span><span className="spec-value">{view}</span></div>}
              <div className="spec-item"><span className="spec-label">Эксклюзив:</span><span className="spec-value">{exclusive ? 'Да' : 'Нет'}</span></div>
            </div>
          </div>
        </div>

        <div className="flat-sidebar apartment-col apartment-rside col">
          <FlatRewardCard rewardsData={rewardsData} />
          <FlatHighlights discountsData={discountsData} mortgageData={mortgageData} installmentsData={installmentsData} />
          <FlatPassport apartment={apartment} block={block} />
          <FlatBlockInfo block={block} />
          <div className="apartment-passport__actions row">
            <div className="col-6">
              <div className="shell-element shell-element_md shell-element_secondary shell-element_radius-lg shell-element_full shell-element_radius btn-wrapper">
                <button type="button" className="btn btn_secondary px-sm"><span className="btn__content justify-content-center">Контакты</span></button>
              </div>
            </div>
            <div className="col-6">
              <div className="shell-element shell-element_md shell-element_brand shell-element_radius-lg shell-element_full shell-element_radius btn-wrapper">
                <button type="button" className="btn btn_brand px-sm"><span className="btn__content justify-content-center">Забронировать</span></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default FlatDetail
