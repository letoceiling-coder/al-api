import { useState, useEffect } from 'react'
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
  const [phone, setPhone] = useState('+79045393434')
  const [password, setPassword] = useState('nwBvh4q')
  const [currentImageIndex, setCurrentImageIndex] = useState(0)

  useEffect(() => {
    loadFlatDetail()
  }, [blockId, apartmentId])

  const loadFlatDetail = async () => {
    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
      }

      const response = await trendAgentAPI.getApartmentFlatDetail(blockId, apartmentId, params)

      if (response.success) {
        setApartmentData(response.data?.apartment || response.data)
        setBlockData(response.data?.block || null)
        setRewardsData(response.data?.rewards || null)
        setDiscountsData(response.data?.discounts || null)
        setMortgageData(response.data?.mortgage || null)
        setInstallmentsData(response.data?.installments || null)
      } else {
        setError(response.message || 'Ошибка загрузки данных квартиры')
      }
    } catch (err) {
      console.error('Ошибка загрузки данных квартиры:', err)
      setError(err.message || 'Ошибка загрузки данных квартиры')
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
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  if (!apartmentData) {
    return (
      <div className="flat-detail-container">
        <div className="error-message">
          <p>Данные квартиры не найдены</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  const apartment = apartmentData.data || apartmentData
  const block = blockData?.data || blockData

  // Собираем все изображения квартиры
  const getImages = () => {
    const images = []
    
    // Проверяем массив images
    if (apartment?.images && Array.isArray(apartment.images)) {
      apartment.images.forEach(img => {
        const imgUrl = getImageUrlFull(img) || getImageUrl(img)
        if (imgUrl) {
          images.push({ url: imgUrl, urlFull: imgUrl })
        }
      })
    }
    
    // Проверяем одиночное image
    if (apartment?.image) {
      const imgUrl = getImageUrlFull(apartment.image) || getImageUrl(apartment.image)
      if (imgUrl && !images.find(img => img.url === imgUrl)) {
        images.push({ url: imgUrl, urlFull: imgUrl })
      }
    }
    
    // Проверяем plan
    if (apartment?.plan) {
      const imgUrl = getImageUrlFull(apartment.plan) || getImageUrl(apartment.plan)
      if (imgUrl && !images.find(img => img.url === imgUrl)) {
        images.push({ url: imgUrl, urlFull: imgUrl })
      }
    }
    
    // Проверяем plan_image
    if (apartment?.plan_image) {
      const imgUrl = getImageUrlFull(apartment.plan_image) || getImageUrl(apartment.plan_image)
      if (imgUrl && !images.find(img => img.url === imgUrl)) {
        images.push({ url: imgUrl, urlFull: imgUrl })
      }
    }
    
    return images
  }

  const images = getImages()
  const currentImage = images[currentImageIndex] || null

  const nextImage = () => {
    setCurrentImageIndex((prev) => (prev + 1) % images.length)
  }

  const prevImage = () => {
    setCurrentImageIndex((prev) => (prev - 1 + images.length) % images.length)
  }

  const selectImage = (index) => {
    setCurrentImageIndex(index)
  }

  const number = apartment.number || apartment.apartment_number || '—'
  const floor = apartment.floor || '—'
  const section = apartment.section_name || apartment.section || '—'
  const building = apartment.building_name || apartment.building || apartment.corpus || '—'
  const area = apartment.privArea || apartment.area || apartment.area_total || null
  const kitchenArea = apartment.kitchenArea || apartment.kitchen_area || null
  const livingArea = apartment.livingArea || apartment.living_area || null
  const finishing = apartment.finishing_name || apartment.finishing || '—'
  const basePrice = apartment.base_price || null
  const fullPrice = apartment.price || apartment.full_price || null
  const pricePerM2 = area && basePrice ? formatPricePerM2(basePrice, area) : '—'
  const status = getStatus(apartment)
  const rooms = apartment.rooms || apartment.room || null
  const view = apartment.view_image || apartment.view || null
  const exclusive = apartment.exclusive || apartment.is_exclusive || false

  return (
    <div className="flat-detail-container">
      {/* Хлебные крошки */}
      <div className="breadcrumbs">
        <button className="btn-back" onClick={() => navigate(-1)}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        {block && (
          <span className="breadcrumb-separator">/</span>
        )}
        {block && (
          <span className="breadcrumb-item">{block.name || block.title || 'Объект'}</span>
        )}
        <span className="breadcrumb-separator">/</span>
        <span className="breadcrumb-item active">Квартира {number}</span>
      </div>

      {/* Заголовок */}
      <div className="flat-header">
        <h1>Квартира {number}</h1>
        {block && (
          <p className="block-name">{block.name || block.title}</p>
        )}
      </div>

      <div className="flat-content">
        <div className="flat-main">
          {/* Галерея изображений */}
          {images.length > 0 && (
            <div className="flat-gallery-section">
              <h2>Фотографии</h2>
              <div className="flat-gallery">
                <div className="gallery-main">
                  {currentImage && (
                    <>
                      {images.length > 1 && (
                        <button 
                          className="gallery-nav-btn gallery-nav-btn-prev"
                          onClick={prevImage}
                          aria-label="Предыдущее изображение"
                        >
                          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M15 18L9 12L15 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                          </svg>
                        </button>
                      )}
                      <img
                        src={currentImage.urlFull || currentImage.url}
                        alt={`Квартира ${number} - фото ${currentImageIndex + 1}`}
                        className="gallery-main-image"
                        onError={(e) => { e.target.style.display = 'none' }}
                      />
                      {images.length > 1 && (
                        <button 
                          className="gallery-nav-btn gallery-nav-btn-next"
                          onClick={nextImage}
                          aria-label="Следующее изображение"
                        >
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
                        <img
                          src={img.url || img.urlFull}
                          alt={`Миниатюра ${index + 1}`}
                          onError={(e) => { e.target.style.display = 'none' }}
                        />
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Характеристики */}
          <div className="flat-specs-section">
            <h2>Характеристики</h2>
            <div className="specs-grid">
              {rooms && (
                <div className="spec-item">
                  <span className="spec-label">Комнат:</span>
                  <span className="spec-value">{rooms === 1 ? 'Студия' : `${rooms}-комн.`}</span>
                </div>
              )}
              {area && (
                <div className="spec-item">
                  <span className="spec-label">Общая площадь:</span>
                  <span className="spec-value">{area} м²</span>
                </div>
              )}
              {livingArea && (
                <div className="spec-item">
                  <span className="spec-label">Жилая площадь:</span>
                  <span className="spec-value">{livingArea} м²</span>
                </div>
              )}
              {kitchenArea && (
                <div className="spec-item">
                  <span className="spec-label">Площадь кухни:</span>
                  <span className="spec-value">{kitchenArea} м²</span>
                </div>
              )}
              <div className="spec-item">
                <span className="spec-label">Этаж:</span>
                <span className="spec-value">{floor}</span>
              </div>
              {section !== '—' && (
                <div className="spec-item">
                  <span className="spec-label">Секция:</span>
                  <span className="spec-value">{section}</span>
                </div>
              )}
              {building !== '—' && (
                <div className="spec-item">
                  <span className="spec-label">Корпус:</span>
                  <span className="spec-value">{building}</span>
                </div>
              )}
              <div className="spec-item">
                <span className="spec-label">Отделка:</span>
                <span className="spec-value">{finishing}</span>
              </div>
              {view && (
                <div className="spec-item">
                  <span className="spec-label">Вид:</span>
                  <span className="spec-value">{view}</span>
                </div>
              )}
              <div className="spec-item">
                <span className="spec-label">Эксклюзив:</span>
                <span className="spec-value">{exclusive ? 'Да' : 'Нет'}</span>
              </div>
            </div>
          </div>
        </div>

        <div className="flat-sidebar apartment-col apartment-rside col">
          {/* Блок вознаграждения */}
          <FlatRewardCard rewardsData={rewardsData} />

          {/* Блоки акций, ипотеки, рассрочки */}
          <FlatHighlights 
            discountsData={discountsData}
            mortgageData={mortgageData}
            installmentsData={installmentsData}
          />

          {/* Паспорт квартиры */}
          <FlatPassport apartment={apartment} block={block} />

          {/* Информация о застройщике и объекте */}
          <FlatBlockInfo block={block} />

          {/* Действия */}
          <div className="apartment-passport__actions row">
            <div className="col-6">
              <div>
                <div className="shell-element shell-element_md shell-element_secondary shell-element_radius-lg shell-element_full shell-element_radius btn-wrapper btn-wrapper_press-effect-animation">
                  <button iconPosition="left" tabIndex="0" className="btn btn_secondary px-sm" type="button">
                    <span className="btn__content justify-content-center">
                      <span>Контакты</span>
                    </span>
                  </button>
                </div>
              </div>
            </div>
            <div className="col-6">
              <div>
                <div className="shell-element shell-element_md shell-element_brand shell-element_radius-lg shell-element_full shell-element_radius btn-wrapper btn-wrapper_press-effect-animation">
                  <button iconPosition="left" tabIndex="0" className="btn btn_brand px-sm" type="button">
                    <span className="btn__content justify-content-center">
                      <span>Забронировать</span>
                    </span>
                  </button>
                </div>
              </div>
            </div>
            <div className="col-12">
              <div className="apartment-form__control">
                <div className="apartment-files__item">
                  <svg className="svg-icon trend-ui-icon-root trend-ui-icon-root__File trend-ui-icon-root__File-20" height="20" width="20" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none">
                    <path xmlns="http://www.w3.org/2000/svg" fillRule="evenodd" clipRule="evenodd" d="M2.00012 3.35C2.00012 1.99029 3.18409 1 4.50012 1H10.7501C10.949 1 11.1398 1.07902 11.2805 1.21967L17.7805 7.71967C17.9211 7.86032 18.0001 8.05109 18.0001 8.25V16.65C18.0001 18.0097 16.8162 19 15.5001 19H4.50012C3.18409 19 2.00012 18.0097 2.00012 16.65V3.35ZM4.50012 2.5C3.88316 2.5 3.50012 2.9424 3.50012 3.35V16.65C3.50012 17.0576 3.88316 17.5 4.50012 17.5H15.5001C16.1171 17.5 16.5001 17.0576 16.5001 16.65V9L10.7501 9.00002C10.5512 9.00003 10.3604 8.92101 10.2198 8.78036C10.0791 8.6397 10.0001 8.44894 10.0001 8.25002V2.5H4.50012ZM11.5001 3.56066L15.4395 7.50001L11.5001 7.50002V3.56066Z" fill="#4C4C4C"></path>
                  </svg>
                  Регламент взаимодействия
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default FlatDetail
