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
import ObjectPlots from '../components/detail/ObjectPlots'
import VillagePassport from '../components/detail/VillagePassport'
import VillageMiniPassport from '../components/detail/VillageMiniPassport'
import VillageReward from '../components/detail/VillageReward'
import VillageDetail from '../components/detail/VillageDetail'
import HousesTable from '../components/detail/HousesTable'
import HousesMap from '../components/detail/HousesMap'
import { getImageUrl } from '../utils/imageUtils'
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
    ...(objectType === 'plots' ? [
      { id: 'plots', label: 'Участки' },
      { id: 'plan', label: 'План посёлка' },
      { id: 'about', label: 'Об объекте' },
      { id: 'mortgage', label: 'Ипотека' },
    ] : []),
    ...(objectType === 'houses' ? [
      { id: 'houses-table', label: 'Таблица' },
      { id: 'houses-plans', label: 'Планы' },
      { id: 'houses-map', label: 'Карта' },
    ] : []),
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

      console.log('ObjectDetail: Loading object', {
        objectType,
        id,
        guid,
        isValidMongoId,
        objectId,
      })

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
        console.log('ObjectDetail: Data loaded successfully', {
          block_id: response.block_id,
          block_guid: response.block_guid,
        })
      } else {
        const errorMessage = response.message || response.error?.message || 'Ошибка загрузки данных'
        console.error('ObjectDetail: API returned error', {
          success: response.success,
          message: errorMessage,
          error: response.error,
        })
        setError(errorMessage)
      }
    } catch (err) {
      console.error('ObjectDetail: Exception during load', {
        error: err,
        message: err.message,
        response: err.response?.data,
      })
      const errorMessage = err.response?.data?.message || 
                          err.response?.data?.error?.message || 
                          err.message || 
                          'Ошибка загрузки данных объекта'
      setError(errorMessage)
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

  // Безопасное извлечение данных с проверкой типов
  // Для участков unified данные могут быть в разных местах
  let unifiedData = {}
  if (objectData.unified) {
    if (objectData.unified.data) {
      unifiedData = objectData.unified.data
    } else if (typeof objectData.unified === 'object' && !objectData.unified.error) {
      unifiedData = objectData.unified
    }
  }
  
  // Логирование для отладки
  if (objectType === 'plots') {
    console.log('ObjectDetail: Plots unified data', {
      hasUnified: !!objectData.unified,
      unifiedKeys: Object.keys(unifiedData),
      hasMinPrices: !!unifiedData.min_prices,
      minPricesCount: Array.isArray(unifiedData.min_prices) ? unifiedData.min_prices.length : 0,
      unifiedData: unifiedData,
    })
  }
  const apartmentsData = objectData.apartments || {}
  const parkingsData = objectData.parkings || {}
  const commerceData = objectData.commerce || {}
  const buildingsData = Array.isArray(objectData.buildings?.data) 
    ? objectData.buildings.data 
    : (Array.isArray(objectData.buildings) ? objectData.buildings : [])
  const plansData = Array.isArray(objectData.plans?.data) 
    ? objectData.plans.data 
    : (Array.isArray(objectData.plans) ? objectData.plans : [])
  const progressData = objectData.progress || {}
  const finishingsData = objectData.finishings || {}
  const advantagesData = objectData.advantages || {}
  const nearbyPlacesData = objectData.nearby_places || {}
  const videosData = objectData.videos || {}
  const filesData = objectData.files || {}

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
            {objectType === 'plots' ? (
              <VillageDetail
                unifiedData={unifiedData}
                advantagesData={advantagesData}
              />
            ) : (
              <ObjectHeader
                objectData={unifiedData}
                advantages={advantagesData}
                buildings={buildingsData}
              />
            )}
          </section>

          {/* Участки */}
          {objectType === 'plots' && (
            <>
              <section id="plots" className="detail-section">
                <ObjectPlots
                  plotsData={null}
                  unifiedData={unifiedData}
                />
              </section>
              
              {/* План посёлка - показываем только если есть данные */}
              {(plansData?.length > 0 || unifiedData?.plan) && (
                <section id="plan" className="detail-section">
                  <div className="village-plan-section">
                    <h2>План посёлка</h2>
                    {unifiedData?.plan ? (
                      <div className="village-plan-content">
                        <img src={unifiedData.plan} alt="План посёлка" className="village-plan-image" />
                      </div>
                    ) : (
                      <div className="plan-placeholder">
                        <p>План посёлка будет отображаться здесь</p>
                      </div>
                    )}
                  </div>
                </section>
              )}
              
              {/* Об объекте - показываем только если есть преимущества или описание */}
              {((Array.isArray(advantagesData?.data) && advantagesData.data.length > 0) || unifiedData?.description || unifiedData?.about) && (
                <section id="about" className="detail-section">
                  <div className="village-about-section">
                    <h2>Об объекте</h2>
                    <div className="village-about-content">
                      {Array.isArray(advantagesData?.data) && advantagesData.data.length > 0 && (
                        <div className="village-advantages-list">
                          {advantagesData.data.map((advantage, index) => (
                            <div key={index} className="advantage-card">
                              {advantage.image && (
                                <img
                                  src={advantage.image.url || advantage.image}
                                  alt={advantage.name || advantage.title}
                                  className="advantage-card-image"
                                />
                              )}
                              <div className="advantage-card-text">
                                {advantage.name || advantage.title || advantage.description}
                              </div>
                            </div>
                          ))}
                        </div>
                      )}
                      {(unifiedData?.description || unifiedData?.about) && (
                        <div className="village-description">
                          <div dangerouslySetInnerHTML={{ __html: unifiedData?.description || unifiedData?.about || '' }} />
                        </div>
                      )}
                    </div>
                  </div>
                </section>
              )}
              
              {/* Ипотека - всегда показываем */}
              <section id="mortgage" className="detail-section">
                <div className="village-mortgage-section">
                  <h2>Ипотека</h2>
                  <div className="mortgage-content">
                    <div className="mortgage-buttons">
                      <button className="card-button">
                        <div className="card-button-icon">📄</div>
                        <div className="card-button-label">Посмотреть программы и ставки</div>
                      </button>
                      <button className="card-button">
                        <div className="card-button-icon">💬</div>
                        <div className="card-button-label">Задать вопрос</div>
                      </button>
                      <button className="card-button">
                        <div className="card-button-icon">📝</div>
                        <div className="card-button-label">Отправить заявку на ипотеку</div>
                      </button>
                    </div>
                  </div>
                </div>
              </section>
            </>
          )}

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

          {/* Дома - Таблица */}
          {objectType === 'houses' && (
            <section id="houses-table" className="detail-section">
              <HousesTable 
                housesData={apartmentsData} 
                unifiedData={unifiedData}
              />
            </section>
          )}

          {/* Дома - Планы */}
          {objectType === 'houses' && plansData && (
            <section id="houses-plans" className="detail-section">
              <div className="houses-plans-section">
                <h2>Планы домов</h2>
                <div className="houses-plans-content">
                  {Array.isArray(plansData?.data) && plansData.data.length > 0 ? (
                    <div className="plans-grid">
                      {plansData.data.map((plan, index) => {
                        const planImage = plan.image?.url || 
                                        (plan.images && plan.images.length > 0 ? getImageUrl(plan.images[0]) : null) ||
                                        plan.image
                        return (
                          <div key={index} className="plan-card">
                            {planImage && (
                              <img src={planImage} alt={plan.name || 'План'} />
                            )}
                            {plan.name && <h3>{plan.name}</h3>}
                            {plan.area && (
                              <div className="plan-info">
                                <span>Площадь: {plan.area} м²</span>
                              </div>
                            )}
                            {plan.rooms && (
                              <div className="plan-info">
                                <span>Комнат: {plan.rooms}</span>
                              </div>
                            )}
                          </div>
                        )
                      })}
                    </div>
                  ) : (
                    <p>Планы домов будут отображаться здесь</p>
                  )}
                </div>
              </div>
            </section>
          )}

          {/* Дома - Карта */}
          {objectType === 'houses' && (
            <section id="houses-map" className="detail-section">
              <HousesMap 
                unifiedData={unifiedData}
                housesData={apartmentsData}
              />
            </section>
          )}

          {/* Паркинги - показываем только если есть данные */}
          {objectType !== 'plots' && (parkingsData?.data?.length > 0 || parkingsData?.length > 0) && (
            <section id="parkings" className="detail-section">
              <ObjectParkings parkingsData={parkingsData} />
            </section>
          )}

          {/* Коммерция - показываем только если есть данные */}
          {objectType !== 'plots' && (commerceData?.data?.length > 0 || commerceData?.length > 0) && (
            <section id="commerce" className="detail-section">
              <ObjectCommerce commerceData={commerceData} />
            </section>
          )}

          {/* Расположение - показываем только если есть адрес или nearby places */}
          {(unifiedData?.address || (nearbyPlacesData?.data && nearbyPlacesData.data.length > 0)) && (
            <section id="location" className="detail-section">
              <ObjectLocation
                address={unifiedData.address}
                nearbyPlaces={nearbyPlacesData}
              />
            </section>
          )}

          {/* Описание - показываем только для не-участков (для участков уже в секции "Об объекте") */}
          {objectType !== 'plots' && (unifiedData?.description || unifiedData?.about) && (
            <section id="description" className="detail-section">
              <ObjectDescription description={unifiedData.description || unifiedData.about} />
            </section>
          )}

          {/* Видео - показываем только если есть данные */}
          {(videosData?.data?.length > 0 || videosData?.length > 0) && (
            <section id="videos" className="detail-section">
              <ObjectVideos videos={videosData} />
            </section>
          )}

          {/* Отделка - показываем только если есть данные */}
          {(finishingsData?.data?.length > 0 || finishingsData?.length > 0) && (
            <section id="finishing" className="detail-section">
              <ObjectFinishing finishings={finishingsData} />
            </section>
          )}

          {/* Ход строительства - показываем только если есть данные */}
          {(progressData?.data?.length > 0 || progressData?.length > 0) && (
            <section id="progress" className="detail-section">
              <ObjectProgress progressData={progressData} />
            </section>
          )}

          {/* Файлы - показываем только если есть данные */}
          {(filesData?.data?.length > 0 || filesData?.length > 0) && (
            <section id="files" className="detail-section">
              <ObjectFiles files={filesData} />
            </section>
          )}

          {/* Преимущества - показываем только для не-участков (для участков уже в секции "Об объекте") */}
          {objectType !== 'plots' && (advantagesData?.data?.length > 0 || advantagesData?.length > 0) && (
            <section id="advantages" className="detail-section">
              <ObjectAdvantages advantages={advantagesData} />
            </section>
          )}
        </div>
      </div>
    </div>
  )
}

export default ObjectDetail
