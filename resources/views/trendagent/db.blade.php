<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>База данных TrendAgent - {{ ucfirst($type) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .nav-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .nav-tab {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #495057;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .nav-tab:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .nav-tab.active {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .stats-bar {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        
        .filters {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-label {
            font-weight: 600;
            color: #495057;
        }
        
        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
            background: #fff;
            cursor: pointer;
        }
        
        .filter-select:hover {
            border-color: #adb5bd;
        }
        
        .data-table {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        .table-content {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            white-space: nowrap;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .price {
            font-weight: 600;
            color: #28a745;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 2rem;
            background: #fff;
            border-top: 1px solid #e9ecef;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            text-decoration: none;
            color: #495057;
            background: #fff;
        }
        
        .pagination a:hover {
            background: #e9ecef;
        }
        
        .pagination .active {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .region-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .region-stat-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        
        .region-stat-name {
            font-weight: 600;
            color: #495057;
        }
        
        .region-stat-count {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            margin-top: 0.5rem;
        }

        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            overflow: auto;
        }

        .modal-content {
            background-color: #fff;
            margin: 2% auto;
            padding: 2rem;
            border-radius: 8px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .close {
            color: #aaa;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #000;
        }

        .modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .modal-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .modal-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
        }

        .info-value {
            color: #212529;
        }

        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .gallery-item {
            cursor: pointer;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .gallery-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        @media (max-width: 768px) {
            .modal-body {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">🏠 TrendAgent DB</div>
            <nav class="nav-tabs">
                <a href="{{ route('trendagent.db.alt', ['type' => 'apartments', 'region' => $region]) }}" 
                   class="nav-tab {{ $type === 'apartments' ? 'active' : '' }}">
                    Квартиры ({{ number_format($statistics['apartments'], 0, ',', ' ') }})
                </a>
                <a href="{{ route('trendagent.db', ['type' => 'complexes', 'region' => $region]) }}" 
                   class="nav-tab {{ $type === 'complexes' ? 'active' : '' }}">
                    Комплексы ({{ number_format($statistics['complexes'], 0, ',', ' ') }})
                </a>
                <a href="{{ route('trendagent.db', ['type' => 'parkings', 'region' => $region]) }}" 
                   class="nav-tab {{ $type === 'parkings' ? 'active' : '' }}">
                    Паркинги ({{ number_format($statistics['parkings'], 0, ',', ' ') }})
                </a>
                <a href="{{ route('trendagent.db', ['type' => 'houses', 'region' => $region]) }}" 
                   class="nav-tab {{ $type === 'houses' ? 'active' : '' }}">
                    Дома ({{ number_format($statistics['houses'], 0, ',', ' ') }})
                </a>
                <a href="{{ route('trendagent.db', ['type' => 'plots', 'region' => $region]) }}" 
                   class="nav-tab {{ $type === 'plots' ? 'active' : '' }}">
                    Участки ({{ number_format($statistics['plots'], 0, ',', ' ') }})
                </a>
                <a href="{{ route('trendagent.db', ['type' => 'commercial', 'region' => $region]) }}" 
                   class="nav-tab {{ $type === 'commercial' ? 'active' : '' }}">
                    Коммерция ({{ number_format($statistics['commercial'], 0, ',', ' ') }})
                </a>
                <a href="{{ route('trendagent.db', ['type' => 'contractors', 'region' => $region]) }}" 
                   class="nav-tab {{ $type === 'contractors' ? 'active' : '' }}">
                    Подрядчики ({{ number_format($statistics['contractors'], 0, ',', ' ') }})
                </a>
            </nav>
        </div>
    </header>
    
    <div class="container">
        <div class="stats-bar">
            <h2 style="margin-bottom: 1rem; color: #495057;">📊 Статистика по регионам</h2>
            <div class="region-stats">
                @foreach($regionStats as $code => $stat)
                    <div class="region-stat-item">
                        <div class="region-stat-name">{{ $stat['name'] }}</div>
                        <div class="region-stat-count">{{ number_format($stat['count'], 0, ',', ' ') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
        
        <div class="filters">
            <form method="GET" action="{{ route('trendagent.db.alt') }}" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="type" value="{{ $type }}">
                
                <label class="filter-label">Регион:</label>
                <select name="region" class="filter-select" onchange="this.form.submit()">
                    <option value="all" {{ $region === 'all' ? 'selected' : '' }}>Все регионы</option>
                    @foreach($regions as $reg)
                        <option value="{{ $reg->code }}" {{ $region === $reg->code ? 'selected' : '' }}>
                            {{ $reg->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
        
        <div class="data-table">
            <div class="table-header">
                {{ ucfirst($type) }} 
                @if($region !== 'all')
                    - {{ $regions->firstWhere('code', $region)->name ?? $region }}
                @endif
                (Всего: {{ number_format($total, 0, ',', ' ') }})
            </div>
            
            @if($data->count() > 0)
                <div class="table-content">
                    <table>
                        <thead>
                            @if($type === 'apartments')
                                <tr>
                                    <th>Планировка</th>
                                    <th>ID</th>
                                    <th>Номер</th>
                                    <th>Комнат</th>
                                    <th>Площадь</th>
                                    <th>Этаж</th>
                                    <th>Цена</th>
                                    <th>Статус</th>
                                    <th>Регион</th>
                                    <th>Действия</th>
                                </tr>
                            @elseif($type === 'complexes')
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Адрес</th>
                                    <th>Застройщик</th>
                                    <th>Регион</th>
                                </tr>
                            @elseif($type === 'parkings')
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Всего мест</th>
                                    <th>Доступно</th>
                                    <th>Цена</th>
                                    <th>Регион</th>
                                </tr>
                            @elseif($type === 'houses')
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Площадь дома</th>
                                    <th>Площадь участка</th>
                                    <th>Цена</th>
                                    <th>Регион</th>
                                </tr>
                            @elseif($type === 'plots')
                                <tr>
                                    <th>ID</th>
                                    <th>Площадь</th>
                                    <th>Цена</th>
                                    <th>Регион</th>
                                </tr>
                            @elseif($type === 'commercial')
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Площадь</th>
                                    <th>Цена</th>
                                    <th>Регион</th>
                                </tr>
                            @elseif($type === 'contractors')
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Описание</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @foreach($data as $item)
                                @if($type === 'apartments')
                                    <tr>
                                        <td style="width: 80px; padding: 0.5rem;">
                                            @php
                                                $planImage = $item->plan_image_url ?? 
                                                    (is_array($item->images) && count($item->images) > 0 ? (is_string($item->images[0]) ? $item->images[0] : ($item->images[0]['url'] ?? null)) : null) ??
                                                    ($item->raw_data['plan_image']['url'] ?? $item->raw_data['plan'] ?? null);
                                            @endphp
                                            @if($planImage)
                                                <img src="{{ $planImage }}" 
                                                     alt="Планировка {{ $item->number ?? '' }}" 
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; cursor: pointer;"
                                                     onclick="showPlanModal('{{ $planImage }}', '{{ $item->number ?? 'Квартира' }}')"
                                                     title="Нажмите для увеличения">
                                            @else
                                                <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 0.7rem;">Нет</div>
                                            @endif
                                        </td>
                                        <td><code style="font-size: 0.85rem;">{{ substr($item->external_id, 0, 12) }}...</code></td>
                                        <td><strong>{{ $item->number ?? '-' }}</strong></td>
                                        <td>{{ $item->rooms ?? '-' }}</td>
                                        <td>{{ $item->area_total ? number_format($item->area_total, 2, ',', ' ') . ' м²' : '-' }}</td>
                                        <td>{{ $item->floor ?? '-' }}</td>
                                        <td class="price">
                                            @if($item->price_base)
                                                {{ number_format($item->price_base, 0, ',', ' ') }} ₽
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->is_booked)
                                                <span class="badge badge-warning">Забронировано</span>
                                            @elseif($item->is_on_request)
                                                <span class="badge badge-info">Под запрос</span>
                                            @else
                                                <span class="badge badge-success">Свободно</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->raw_data && isset($item->raw_data['city']))
                                                {{ $item->raw_data['city']['name'] ?? $item->raw_data['city']['guid'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <button onclick="showApartmentDetails({{ $item->id }})" 
                                                    style="padding: 0.25rem 0.75rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.85rem;">
                                                Детали
                                            </button>
                                        </td>
                                    </tr>
                                @elseif($type === 'complexes')
                                    <tr>
                                        <td><code style="font-size: 0.85rem;">{{ substr($item->external_id, 0, 12) }}...</code></td>
                                        <td><strong>{{ $item->name ?? '-' }}</strong></td>
                                        <td>{{ $item->address ?? '-' }}</td>
                                        <td>{{ $item->developer_name ?? '-' }}</td>
                                        <td>{{ $item->region->name ?? '-' }}</td>
                                    </tr>
                                @elseif($type === 'parkings')
                                    <tr>
                                        <td><code style="font-size: 0.85rem;">{{ substr($item->external_id, 0, 12) }}...</code></td>
                                        <td><strong>{{ $item->name ?? '-' }}</strong></td>
                                        <td>{{ $item->total_places ?? '-' }}</td>
                                        <td>{{ $item->available_places ?? '-' }}</td>
                                        <td class="price">
                                            @if($item->price_base)
                                                {{ number_format($item->price_base, 0, ',', ' ') }} ₽
                                            @elseif($item->price_per_month)
                                                {{ number_format($item->price_per_month, 0, ',', ' ') }} ₽/мес
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->raw_data && isset($item->raw_data['city']))
                                                {{ $item->raw_data['city']['name'] ?? $item->raw_data['city']['guid'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @elseif($type === 'houses')
                                    <tr>
                                        <td><code style="font-size: 0.85rem;">{{ substr($item->external_id, 0, 12) }}...</code></td>
                                        <td><strong>{{ $item->name ?? ($item->raw_data['name'] ?? '-') }}</strong></td>
                                        <td>{{ $item->house_area ? number_format($item->house_area, 2, ',', ' ') . ' м²' : ($item->raw_data['house_area'] ?? '-') }}</td>
                                        <td>{{ $item->land_area ? number_format($item->land_area, 2, ',', ' ') . ' м²' : ($item->raw_data['land_area'] ?? '-') }}</td>
                                        <td class="price">
                                            @if($item->price_base)
                                                {{ number_format($item->price_base, 0, ',', ' ') }} ₽
                                            @elseif($item->raw_data && isset($item->raw_data['price']))
                                                {{ number_format($item->raw_data['price'], 0, ',', ' ') }} ₽
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->raw_data && isset($item->raw_data['city']))
                                                {{ $item->raw_data['city']['name'] ?? $item->raw_data['city']['guid'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @elseif($type === 'plots')
                                    <tr>
                                        <td><code style="font-size: 0.85rem;">{{ substr($item->external_id, 0, 12) }}...</code></td>
                                        <td>{{ $item->area ? number_format($item->area, 2, ',', ' ') . ' м²' : ($item->raw_data['area'] ?? '-') }}</td>
                                        <td class="price">
                                            @if($item->price_base)
                                                {{ number_format($item->price_base, 0, ',', ' ') }} ₽
                                            @elseif($item->raw_data && isset($item->raw_data['price']))
                                                {{ number_format($item->raw_data['price'], 0, ',', ' ') }} ₽
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->raw_data && isset($item->raw_data['city']))
                                                {{ $item->raw_data['city']['name'] ?? $item->raw_data['city']['guid'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @elseif($type === 'commercial')
                                    <tr>
                                        <td><code style="font-size: 0.85rem;">{{ substr($item->external_id, 0, 12) }}...</code></td>
                                        <td><strong>{{ $item->name ?? ($item->raw_data['name'] ?? '-') }}</strong></td>
                                        <td>{{ $item->area_total ? number_format($item->area_total, 2, ',', ' ') . ' м²' : ($item->raw_data['area_total'] ?? $item->raw_data['area'] ?? '-') }}</td>
                                        <td class="price">
                                            @if($item->price_base)
                                                {{ number_format($item->price_base, 0, ',', ' ') }} ₽
                                            @elseif($item->raw_data && isset($item->raw_data['price']))
                                                {{ number_format($item->raw_data['price'], 0, ',', ' ') }} ₽
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($item->raw_data && isset($item->raw_data['city']))
                                                {{ $item->raw_data['city']['name'] ?? $item->raw_data['city']['guid'] ?? '-' }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @elseif($type === 'contractors')
                                    <tr>
                                        <td><code style="font-size: 0.85rem;">{{ substr($item->external_id, 0, 12) }}...</code></td>
                                        <td><strong>{{ $item->name ?? '-' }}</strong></td>
                                        <td>{{ \Illuminate\Support\Str::limit($item->description ?? '-', 100) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($totalPages > 1)
                    <div class="pagination">
                        @if($page > 1)
                            <a href="{{ route('trendagent.db.alt', ['type' => $type, 'region' => $region, 'page' => $page - 1]) }}">← Назад</a>
                        @else
                            <span class="disabled">← Назад</span>
                        @endif
                        
                        @for($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++)
                            @if($i === $page)
                                <span class="active">{{ $i }}</span>
                            @else
                                <a href="{{ route('trendagent.db.alt', ['type' => $type, 'region' => $region, 'page' => $i]) }}">{{ $i }}</a>
                            @endif
                        @endfor
                        
                        @if($page < $totalPages)
                            <a href="{{ route('trendagent.db.alt', ['type' => $type, 'region' => $region, 'page' => $page + 1]) }}">Вперед →</a>
                        @else
                            <span class="disabled">Вперед →</span>
                        @endif
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h3>Нет данных</h3>
                    <p>В базе данных нет записей для выбранных фильтров.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Модальное окно для планировки -->
    <div id="planModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2 class="modal-title" id="planModalTitle">Планировка</h2>
                <span class="close" onclick="closePlanModal()">&times;</span>
            </div>
            <div style="text-align: center;">
                <img id="planModalImage" src="" alt="Планировка" class="modal-image" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>

    <!-- Модальное окно для детальной информации -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Детальная информация</h2>
                <span class="close" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div id="detailsModalBody" class="modal-body">
                <div class="loading">Загрузка...</div>
            </div>
        </div>
    </div>

    <script>
        function showPlanModal(imageUrl, title) {
            document.getElementById('planModalTitle').textContent = 'Планировка: ' + title;
            document.getElementById('planModalImage').src = imageUrl;
            document.getElementById('planModal').style.display = 'block';
        }

        function closePlanModal() {
            document.getElementById('planModal').style.display = 'none';
        }

        function showApartmentDetails(apartmentId) {
            const modal = document.getElementById('detailsModal');
            const body = document.getElementById('detailsModalBody');
            modal.style.display = 'block';
            body.innerHTML = '<div class="loading">Загрузка...</div>';

            fetch(`/api/trendagent/db/apartment/${apartmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const apt = data.data;
                        const planImage = apt.plan_image_url || 
                            (apt.images && apt.images.length > 0 ? 
                                (typeof apt.images[0] === 'string' ? apt.images[0] : apt.images[0].url) : null) ||
                            (apt.raw_data?.plan_image?.url || apt.raw_data?.plan);

                        let imagesHtml = '';
                        if (apt.images && Array.isArray(apt.images) && apt.images.length > 0) {
                            imagesHtml = '<div class="image-gallery">';
                            apt.images.forEach(img => {
                                const imgUrl = typeof img === 'string' ? img : (img.url || img);
                                if (imgUrl) {
                                    imagesHtml += `<div class="gallery-item" onclick="showPlanModal('${imgUrl}', '${apt.number || 'Квартира'}')">
                                        <img src="${imgUrl}" alt="Изображение">
                                    </div>`;
                                }
                            });
                            imagesHtml += '</div>';
                        }

                        body.innerHTML = `
                            <div style="grid-column: 1 / -1;">
                                ${planImage ? `<img src="${planImage}" alt="Планировка" class="modal-image" style="max-width: 100%; margin-bottom: 1rem;">` : ''}
                            </div>
                            <div class="modal-info">
                                <div class="info-row">
                                    <span class="info-label">Номер квартиры:</span>
                                    <span class="info-value">${apt.number || '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Комнат:</span>
                                    <span class="info-value">${apt.rooms || '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Общая площадь:</span>
                                    <span class="info-value">${apt.area_total ? apt.area_total.toFixed(2) + ' м²' : '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Жилая площадь:</span>
                                    <span class="info-value">${apt.area_living ? apt.area_living.toFixed(2) + ' м²' : '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Площадь кухни:</span>
                                    <span class="info-value">${apt.area_kitchen ? apt.area_kitchen.toFixed(2) + ' м²' : '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Этаж:</span>
                                    <span class="info-value">${apt.floor || '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Базовая цена:</span>
                                    <span class="info-value">${apt.price_base ? new Intl.NumberFormat('ru-RU').format(apt.price_base) + ' ₽' : '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Полная цена:</span>
                                    <span class="info-value">${apt.price_full ? new Intl.NumberFormat('ru-RU').format(apt.price_full) + ' ₽' : '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Цена за м²:</span>
                                    <span class="info-value">${apt.price_per_sqm ? new Intl.NumberFormat('ru-RU').format(apt.price_per_sqm) + ' ₽' : '-'}</span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Статус:</span>
                                    <span class="info-value">
                                        ${apt.is_booked ? '<span class="badge badge-warning">Забронировано</span>' : 
                                          apt.is_on_request ? '<span class="badge badge-info">Под запрос</span>' : 
                                          '<span class="badge badge-success">Свободно</span>'}
                                    </span>
                                </div>
                                ${apt.complex ? `
                                <div class="info-row">
                                    <span class="info-label">Комплекс:</span>
                                    <span class="info-value">${apt.complex.name || '-'}</span>
                                </div>
                                ` : ''}
                            </div>
                            ${imagesHtml ? `<div style="grid-column: 1 / -1; margin-top: 1rem;">
                                <h3 style="margin-bottom: 1rem;">Галерея изображений</h3>
                                ${imagesHtml}
                            </div>` : ''}
                        `;
                    } else {
                        body.innerHTML = '<div class="loading">Ошибка загрузки данных</div>';
                    }
                })
                .catch(error => {
                    body.innerHTML = '<div class="loading">Ошибка: ' + error.message + '</div>';
                });
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Закрытие модальных окон при клике вне их
        window.onclick = function(event) {
            const planModal = document.getElementById('planModal');
            const detailsModal = document.getElementById('detailsModal');
            if (event.target === planModal) {
                closePlanModal();
            }
            if (event.target === detailsModal) {
                closeDetailsModal();
            }
        }
    </script>
</body>
</html>
