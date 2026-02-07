import { useTranslation } from 'react-i18next'
import './Page.css'

const StreamingGuide = () => {
  const { t } = useTranslation()

  return (
    <div className="page">
      <div className="page-container">
        <h1>{t('streaming.title')}</h1>
        <p className="subtitle">
          {t('streaming.subtitle')}
          <span className="badge new">{t('streaming.badgeNew')}</span>
          <span className="badge">{t('streaming.badgeV1')}</span>
        </p>

        <section>
          <h2>{t('streaming.overview')}</h2>
          <p>{t('streaming.overviewDesc')}</p>
          <ul>
            <li>{t('streaming.overviewItem1')}</li>
            <li>{t('streaming.overviewItem2')}</li>
            <li>{t('streaming.overviewItem3')}</li>
            <li>{t('streaming.overviewItem4')}</li>
          </ul>

          <div className="info-box">
            <strong>{t('streaming.keyDifferences')}</strong><br />
            • <code>{t('streaming.keyDiff1')}</code><br />
            • <code>{t('streaming.keyDiff2')}</code>
          </div>
        </section>

        <section>
          <h2>{t('streaming.endpoint')}</h2>
          <pre className="code-block"><code>POST https://api.siteaccess.ru/api/v1/ai/stream</code></pre>

          <h3>{t('streaming.headers')}</h3>
          <table>
            <thead>
              <tr>
                <th>Header</th>
                <th>Value</th>
                <th>{t('common.required')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>Authorization</code></td>
                <td><code>Bearer {'{token}'}</code></td>
                <td>✅ {t('common.yes')}</td>
              </tr>
              <tr>
                <td><code>Content-Type</code></td>
                <td><code>application/json</code></td>
                <td>✅ {t('common.yes')}</td>
              </tr>
              <tr>
                <td><code>X-Trace-ID</code></td>
                <td>{t('streaming.customTraceId')}</td>
                <td>❌ {t('common.no')}</td>
              </tr>
            </tbody>
          </table>

          <h3>{t('streaming.requestBody')}</h3>
          <p>{t('streaming.requestBodyDesc')}</p>
          <pre className="code-block"><code>{`{
  "provider": "gemini",
  "model": "gemini-1.5-pro",
  "prompt": "Write a short story about AI",
  "parameters": {
    "temperature": 0.7,
    "max_tokens": 1000
  }
}`}</code></pre>
        </section>

        <section>
          <h2>{t('streaming.responseFormat')}</h2>

          <h3>{t('streaming.eventTypes')}</h3>
          <table>
            <thead>
              <tr>
                <th>Event</th>
                <th>{t('common.description')}</th>
                <th>When</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><span className="event-type">start</span></td>
                <td>{t('streaming.eventStartDesc')}</td>
                <td>{t('streaming.eventStartWhen')}</td>
              </tr>
              <tr>
                <td><span className="event-type">token</span></td>
                <td>{t('streaming.eventTokenDesc')}</td>
                <td>{t('streaming.eventTokenWhen')}</td>
              </tr>
              <tr>
                <td><span className="event-type">done</span></td>
                <td>{t('streaming.eventDoneDesc')}</td>
                <td>{t('streaming.eventDoneWhen')}</td>
              </tr>
              <tr>
                <td><span className="event-type">error</span></td>
                <td>{t('streaming.eventErrorDesc')}</td>
                <td>{t('streaming.eventErrorWhen')}</td>
              </tr>
            </tbody>
          </table>

          <h3>{t('streaming.exampleStream')}</h3>
          <pre className="code-block"><code>{`event: start
data: {"request_id":"550e8400-...","trace_id":"...","provider":"gemini","model":"gemini-1.5-pro","timestamp":"2026-02-06T16:00:00Z"}

event: token
data: {"content":"Once ","index":0}

event: token
data: {"content":"upon ","index":1}

event: token
data: {"content":"a ","index":2}

event: token
data: {"content":"time...","index":3}

event: done
data: {"request_id":"550e8400-...","usage":{"prompt_tokens":10,"completion_tokens":50,"total_tokens":60,"estimated_cost":0.00075},"metadata":{"processing_time":2.345,"timestamp":"2026-02-06T16:00:10Z","api_key_source":"internal"},"finish_reason":"stop"}`}</code></pre>
        </section>

        <section>
          <h2>{t('streaming.clientExamples')}</h2>

          <h3>{t('streaming.javascriptBrowser')}</h3>
          <pre className="code-block"><code>{`const token = 'your-bearer-token';

const eventSource = new EventSource(
  'https://api.siteaccess.ru/api/v1/ai/stream',
  {
    headers: {
      'Authorization': \`Bearer \${token}\`,
      'Content-Type': 'application/json'
    },
    method: 'POST',
    body: JSON.stringify({
      provider: 'gemini',
      model: 'gemini-1.5-pro',
      prompt: 'Write a short story about AI'
    })
  }
);

// ${t('streaming.handleStart')}
eventSource.addEventListener('start', (e) => {
  const data = JSON.parse(e.data);
  console.log('Stream started:', data.request_id);
});

// ${t('streaming.handleToken')}
eventSource.addEventListener('token', (e) => {
  const data = JSON.parse(e.data);
  document.getElementById('output').innerHTML += data.content;
});

// ${t('streaming.handleCompletion')}
eventSource.addEventListener('done', (e) => {
  const data = JSON.parse(e.data);
  console.log('Stream completed:', data.usage);
  eventSource.close();
});

// ${t('streaming.handleErrors')}
eventSource.addEventListener('error', (e) => {
  const data = JSON.parse(e.data);
  console.error('Stream error:', data.detail);
  eventSource.close();
});`}</code></pre>

          <h3>{t('streaming.javascriptFetch')}</h3>
          <pre className="code-block"><code>{`async function streamAI() {
  const response = await fetch('https://api.siteaccess.ru/api/v1/ai/stream', {
    method: 'POST',
    headers: {
      'Authorization': \`Bearer \${token}\`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      provider: 'gemini',
      model: 'gemini-1.5-pro',
      prompt: 'Write a short story about AI'
    })
  });

  const reader = response.body.getReader();
  const decoder = new TextDecoder();

  while (true) {
    const { done, value } = await reader.read();
    if (done) break;

    const chunk = decoder.decode(value);
    const lines = chunk.split('\\n');

    for (const line of lines) {
      if (line.startsWith('data: ')) {
        const data = JSON.parse(line.substring(6));
        
        if (data.content) {
          // ${t('streaming.tokenReceived')}
          document.getElementById('output').innerHTML += data.content;
        } else if (data.usage) {
          // ${t('streaming.streamCompleted')}
          console.log('Usage:', data.usage);
        }
      }
    }
  }
}`}</code></pre>

          <h3>{t('streaming.python')}</h3>
          <pre className="code-block"><code>{`import requests
import json

def stream_ai():
    url = 'https://api.siteaccess.ru/api/v1/ai/stream'
    headers = {
        'Authorization': f'Bearer {token}',
        'Content-Type': 'application/json'
    }
    data = {
        'provider': 'gemini',
        'model': 'gemini-1.5-pro',
        'prompt': 'Write a short story about AI'
    }

    with requests.post(url, headers=headers, json=data, stream=True) as response:
        for line in response.iter_lines():
            if line:
                line = line.decode('utf-8')
                
                if line.startswith('event: '):
                    event = line.split(': ')[1]
                    
                elif line.startswith('data: '):
                    data = json.loads(line[6:])
                    
                    if event == 'start':
                        print(f"Stream started: {data['request_id']}")
                    
                    elif event == 'token':
                        print(data['content'], end='', flush=True)
                    
                    elif event == 'done':
                        print(f"\\n\\nUsage: {data['usage']}")
                    
                    elif event == 'error':
                        print(f"\\nError: {data['detail']}")
                        break

stream_ai()`}</code></pre>

          <h3>{t('streaming.curl')}</h3>
          <pre className="code-block"><code>{`curl -N -X POST https://api.siteaccess.ru/api/v1/ai/stream \\
  -H "Authorization: Bearer your-token" \\
  -H "Content-Type: application/json" \\
  -d '{
    "provider": "gemini",
    "model": "gemini-1.5-pro",
    "prompt": "Write a short story about AI"
  }'`}</code></pre>
          <div className="info-box">
            <strong>{t('streaming.curlNote')}</strong>
          </div>
        </section>

        <section>
          <h2>{t('streaming.bestPractices')}</h2>

          <h3>1. {t('streaming.handleTimeouts')}</h3>
          <p>{t('streaming.handleTimeoutsDesc')}</p>
          <pre className="code-block"><code>{`eventSource.onerror = () => {
  console.log('Connection lost, reconnecting...');
  setTimeout(() => connectStream(), 5000);
};`}</code></pre>

          <h3>2. {t('streaming.bufferIncomplete')}</h3>
          <p>{t('streaming.bufferIncompleteDesc')}</p>
          <pre className="code-block"><code>{`let buffer = '';
eventSource.addEventListener('token', (e) => {
  buffer += JSON.parse(e.data).content;
  if (buffer.endsWith('.') || buffer.endsWith('!') || buffer.endsWith('?')) {
    renderSentence(buffer);
    buffer = '';
  }
});`}</code></pre>

          <h3>3. {t('streaming.closeConnections')}</h3>
          <p>{t('streaming.closeConnectionsDesc')}</p>
          <pre className="code-block"><code>{`eventSource.addEventListener('done', () => {
  eventSource.close();
});

// ${t('streaming.alsoHandleUnmount')}
useEffect(() => {
  return () => eventSource.close();
}, []);`}</code></pre>

          <div className="warning-box">
            <strong>{t('streaming.important')}</strong><br />
            • {t('streaming.importantItem1')}<br />
            • {t('streaming.importantItem2')}<br />
            • {t('streaming.importantItem3')}<br />
            • {t('streaming.importantItem4')}
          </div>
        </section>

        <section>
          <h2>{t('streaming.errorHandling')}</h2>
          <p>{t('streaming.errorHandlingDesc')}</p>
          <pre className="code-block"><code>{`event: error
data: {"type":"https://api.siteaccess.ru/docs/errors/rate-limit-exceeded","title":"Rate Limit Exceeded","status":429,"detail":"Daily request limit of 100 exceeded","trace_id":"550e8400-...","timestamp":"2026-02-06T16:00:00Z"}`}</code></pre>
        </section>

        <section>
          <h2>{t('streaming.responseHeaders')}</h2>
          <table>
            <thead>
              <tr>
                <th>Header</th>
                <th>{t('common.description')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>Content-Type</code></td>
                <td><code>text/event-stream</code></td>
              </tr>
              <tr>
                <td><code>Cache-Control</code></td>
                <td><code>no-cache</code></td>
              </tr>
              <tr>
                <td><code>Connection</code></td>
                <td><code>keep-alive</code></td>
              </tr>
              <tr>
                <td><code>X-Trace-ID</code></td>
                <td>{t('streaming.requestTraceId')}</td>
              </tr>
            </tbody>
          </table>
        </section>

        <section>
          <h2>{t('streaming.performance')}</h2>
          <ul>
            <li><strong>{t('streaming.performanceItem1')}</strong></li>
            <li><strong>{t('streaming.performanceItem2')}</strong></li>
            <li><strong>{t('streaming.performanceItem3')}</strong></li>
            <li><strong>{t('streaming.performanceItem4')}</strong></li>
          </ul>
        </section>

        <section>
          <h2>{t('streaming.whenToUse')}</h2>
          <table>
            <thead>
              <tr>
                <th>{t('streaming.useStreamingWhen')}</th>
                <th>{t('streaming.useStandardWhen')}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>• {t('streaming.streamingWhen1')}</td>
                <td>• {t('streaming.standardWhen1')}</td>
              </tr>
              <tr>
                <td>• {t('streaming.streamingWhen2')}</td>
                <td>• {t('streaming.standardWhen2')}</td>
              </tr>
              <tr>
                <td>• {t('streaming.streamingWhen3')}</td>
                <td>• {t('streaming.standardWhen3')}</td>
              </tr>
              <tr>
                <td>• {t('streaming.streamingWhen4')}</td>
                <td>• {t('streaming.standardWhen4')}</td>
              </tr>
            </tbody>
          </table>
        </section>
      </div>
    </div>
  )
}

export default StreamingGuide
