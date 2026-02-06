import { useState, useEffect } from 'react'
import { useParams, useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import ObjectHeader from '../components/detail/ObjectHeader'
import ObjectApartments from '../components/detail/ObjectApartments'
import ObjectParkings from '../components/detail/ObjectParkings'
import ObjectCommerce from '../components/detail/ObjectCommerce'
import ObjectLocation from '../components/detail/ObjectLocation'
import ObjectDescription from '../components/detail/ObjectDescription'
import ObjectVideos from '../components/detail/ObjectVideos'
import ObjectFinishing from '../components/detail/ObjectFinishing'
import ObjectProgress from '../components/detail/ObjectProgress'
import ObjectFiles from '../components/detail/ObjectFiles'
import ObjectAdvantages from '../components/detail/ObjectAdvantages'
import '../pages/ObjectDetail.css'

const ObjectDetail = () => {
  const { objectType, id } = useParams()
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const guid = searchParams.get('guid')

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [objectData, setObjectData] = useState(null)
  const [phone, setPhone] = useState('+79045393434')
  const [password, setPassword] = useState('nwBvh4q')

  const sections = [
    { id: 'header', label: 'Общая информация' },
    { id: 'apartments', label: 'Квартиры' },
    { id: 'parkings', label: 'Паркинги' },
    { id: 'commerce', label: 'Коммерция' },
    { id: 'location', label: 'Расположение' },
    { id: 'description', label: 'Описание' },
    { id: 'videos', label: 'Видео' },
    { id: 'finishing', label: 'Отделка' },
    { id: 'progress', label: 'Ход строительства' },
    { id: 'files', label: 'Файлы' },
    { id: 'advantages', label: 'Преимущества' },
  ]

  useEffect(() => {
    loadObjectDetail()
  }, [id, objectType, guid])

  const loadObjectDetail = async () => {
    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
        options: {
          unified: true,
          buildings: true,
          apartments: true,
          plans: true,
          progress: true,
          finishings: true,
          advantages: true,
          nearby_places: true,
          min_price: true,
          videos: true,
          files: true,
        },
      }

      let response
      // Определяем, какой идентификатор использовать
      // Приоритет: если id является валидным MongoDB ObjectId (24 символа hex), используем id
      // Иначе используем guid, если он есть
      const isValidMongoId = id && /^[a-f0-9]{24}$/i.test(id)
      const objectId = isValidMongoId ? id : (guid || id)

      if (objectType === 'apartments') {
        response = await trendAgentAPI.getApartmentDetail(objectId, params)
      } else if (objectType === 'parkings') {
        response = await trendAgentAPI.getParkingDetail(objectId, params)
      } else if (objectType === 'houses') {
        response = await trendAgentAPI.getHouseDetail(objectId, params)
      } else if (objectType === 'plots') {
        response = await trendAgentAPI.getPlotDetail(objectId, params)
      } else if (objectType === 'commercial') {
        response = await trendAgentAPI.getCommercialDetail(objectId, params)
      } else {
        throw new Error('Неизвестный тип объекта')
      }

      if (response.success) {
        setObjectData(response.data)
      } else {
        setError(response.message || 'Ошибка загрузки данных')
      }
    } catch (err) {
      console.error('Ошибка загрузки детальной информации:', err)
      setError(err.message || 'Ошибка загрузки данных объекта')
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="container mx-auto p-6">
        <div className="card text-center">
          <div className="loading">
            <div className="spinner"></div>
            <p className="ml-4">Загрузка данных объекта...</p>
          </div>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="container mx-auto p-6">
        <div className="card">
          <div className="error-message">
            <p className="text-destructive">{error}</p>
            <button className="btn btn-primary mt-4" onClick={() => navigate('/')}>
              Вернуться к списку
            </button>
          </div>
        </div>
      </div>
    )
  }

  if (!objectData) {
    return null
  }

  const unifiedData = objectData.unified?.data || objectData.unified || {}
  const apartmentsData = objectData.apartments || {}
  const parkingsData = objectData.parkings || {}
  const commerceData = objectData.commerce || {}
  const buildingsData = objectData.buildings?.data || objectData.buildings || []
  const plansData = objectData.plans?.data || objectData.plans || []
  const progressData = objectData.progress || {}
  const finishingsData = objectData.finishings?.data || objectData.finishings || []
  const advantagesData = objectData.advantages?.data || objectData.advantages || []
  const nearbyPlacesData = objectData.nearby_places?.data || objectData.nearby_places || []
  const videosData = objectData.videos?.data || objectData.videos || []
  const filesData = objectData.files?.data || objectData.files || []

  return (
    <div className="object-detail">
      <div className="container mx-auto p-6">
        {/* Кнопка назад */}
        <button
          className="btn-back"
          onClick={() => navigate('/')}
        >
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Назад к списку
        </button>

        {/* Sticky навигация по секциям */}
        <div className="detail-navigation">
          <div className="nav-tabs">
            {sections.map((section) => (
              <a
                key={section.id}
                href={`#${section.id}`}
                className="nav-tab"
              >
                {section.label}
              </a>
            ))}
          </div>
        </div>

        {/* Секции детальной информации */}
        <div className="detail-sections">
          {/* Заголовок объекта */}
          <section id="header" className="detail-section">
            <ObjectHeader
              objectData={unifiedData}
              advantages={advantagesData}
              buildings={buildingsData}
            />
          </section>

          {/* Квартиры */}
          {objectType === 'apartments' && (
            <section id="apartments" className="detail-section">
              <ObjectApartments
                apartmentsData={apartmentsData}
                plansData={plansData}
                buildingsData={buildingsData}
              />
            </section>
          )}

          {/* Паркинги */}
          <section id="parkings" className="detail-section">
            <ObjectParkings parkingsData={parkingsData} />
          </section>

          {/* Коммерция */}
          <section id="commerce" className="detail-section">
            <ObjectCommerce commerceData={commerceData} />
          </section>

          {/* Расположение */}
          <section id="location" className="detail-section">
            <ObjectLocation
              address={unifiedData.address}
              nearbyPlaces={nearbyPlacesData}
            />
          </section>

          {/* Описание */}
          <section id="description" className="detail-section">
            <ObjectDescription description={unifiedData.description || unifiedData.about} />
          </section>

          {/* Видео */}
          <section id="videos" className="detail-section">
            <ObjectVideos videos={videosData} />
          </section>

          {/* Отделка */}
          <section id="finishing" className="detail-section">
            <ObjectFinishing finishings={finishingsData} />
          </section>

          {/* Ход строительства */}
          <section id="progress" className="detail-section">
            <ObjectProgress progressData={progressData} />
          </section>

          {/* Файлы */}
          <section id="files" className="detail-section">
            <ObjectFiles files={filesData} />
          </section>

          {/* Преимущества */}
          <section id="advantages" className="detail-section">
            <ObjectAdvantages advantages={advantagesData} />
          </section>
        </div>
      </div>
    </div>
  )
}

export default ObjectDetail
