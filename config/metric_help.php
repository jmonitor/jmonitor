<?php

declare(strict_types=1);

use App\Metrics\Metric;

/**
 * Metric help contents:
 * - definitions: What it is.
 * - why_it_matters: Why this data should be monitored.
 * - how_to_read: How to interpret the numbers/charts.
 * - actions: What to do when an alert fires.
 * - good_to_know: An extra tip or expert insight.
 */
return [
    Metric::SystemRamUsage->value => [
        'definitions' => [
            'Ram usage is the percentage of total system memory currently used by the operating system and applications.',
        ],
        'why_it_matters' => [
            'RAM is critical: if it runs out, the system will swap to disk (very slow) or crash applications to save itself.',
        ],
        'how_to_read' => [
            'Consistently high usage (above 90%) is a warning. Occasional spikes are usually normal.',
        ],
        'actions' => [
            [
                'case' => 'If RAM usage is consistently > 80–90%',
                'actions' => ['Identify memory-hungry processes, optimize your app, or add more RAM.'],
            ],
            [
                'case' => 'If RAM spikes suddenly',
                'actions' => ['Check for recent deployments, traffic surges, or potential memory leaks.'],
            ],
            [
                'case' => 'If RAM usage is very low for long periods',
                'actions' => [
                    'This is usually not a problem, but it may indicate that the server is over-provisioned and resources could be reduced to save costs.',
                ],
            ],
        ],
        'good_to_know' => [
            'Linux often uses "free" RAM to cache files. High usage isn\'t always bad as long as "available" memory remains sufficient.',
        ],
    ],

    Metric::SystemCpuUsage->value => [
        'definitions' => [
            'CPU usage is the percentage of the processor\'s capacity currently being used.',
        ],
        'why_it_matters' => [
            'High CPU usage slows down response times. If it hits 100%, the server becomes unresponsive.',
        ],
        'how_to_read' => [
            'Short spikes during heavy tasks are fine. Sustained high usage indicates an overloaded server.',
        ],
        'actions' => [
            [
                'case' => 'If CPU usage is consistently > 80%',
                'actions' => ['Optimize inefficient code, scale your server, or check for background tasks (crons).'],
            ],
            [
                'case' => 'If CPU usage is very low most of the time',
                'actions' => [
                    'This is usually fine, but it may indicate that the server is oversized for its current workload.',
                ],
            ],
        ],
        'good_to_know' => [
            'A 100% spike on a 4-core CPU doesn\'t mean the server is dead; it might just be one core working hard on a single task.',
        ],
    ],

    Metric::SystemDiskUsage->value => [
        'definitions' => [
            'Disk usage is the percentage of total storage space used on the server.',
        ],
        'why_it_matters' => [
            'If the disk is full, the database will stop writing, logs will fail, and the system may crash.',
        ],
        'how_to_read' => [
            'Monitor the growth trend. A sudden jump is more worrying than a slow, steady increase.',
        ],
        'actions' => [
            [
                'case' => 'If disk usage is > 80–90%',
                'actions' => ['Clean up logs, temporary files, or old backups. Increase disk capacity if needed.'],
            ],
        ],
        'good_to_know' => [
            'SSD performance can drop significantly when they are more than 90% full.',
        ],
    ],

    Metric::ApacheVersion->value => [
        'definitions' => [
            'Apache HTTP Server is one of the most widely used open-source web servers, commonly used to serve websites and web applications.',
            'Endoflife timeline: <a href="https://endoflife.date/apache-http-server" target="_blank">https://endoflife.date/apache-http-server</a>.',
        ],
        'why_it_matters' => [
            'Old versions are vulnerable to security exploits and lack modern performance fixes.',
        ],
        'how_to_read' => ['Compare with the latest stable version available online.'],
        'good_to_know' => [
            'Updating Apache is often the simplest way to patch "Zero Day" security vulnerabilities.',
        ],
    ],
    Metric::ApacheBusyWorkers->value => [
        'definitions' => [
            'Busy workers represent the specific execution slots (threads) that are actively processing a request at this exact moment.',
            'Difference between worker and process: A worker is an individual execution unit (thread) that handles a request, while a process is the memory-allocated container that can host multiple workers.',
        ],
        'why_it_matters' => [
            'It represents your server\'s real-time capacity. If all workers are busy, your server is "saturated" and new visitors will experience long loading times or timeouts.',
        ],
        'how_to_read' => [
            'A high number is normal during traffic peaks. However, if it stays flat at your maximum configured limit, you have a bottleneck.',
        ],
        'actions' => [
            [
                'case' => 'If busy workers frequently hit the maximum limit',
                'actions' => [
                    'Increase "MaxRequestWorkers" in your Apache config (if RAM allows), or identify slow PHP scripts that are keeping workers occupied for too long.',
                ],
            ],
            [
                'case' => 'If the number of busy workers is low despite slow site speed',
                'actions' => [
                    'The bottleneck is likely outside of Apache, such as a network issue, a firewall restriction, or a slow database lock.',
                ],
            ],
        ],
        'good_to_know' => [
            'Think of workers as checkout lanes in a supermarket: if all lanes are busy, a queue forms outside. Adding more lanes (workers) helps, but only if the "cashiers" (CPU/RAM) can keep up.',
        ],
    ],
    Metric::ApacheBusyProcess->value => [
        'definitions' => [
            'Busy processes represent the number of Apache instances currently processing requests.',
            'Difference between worker and process: A worker is an individual execution unit (thread) that handles a request, while a process is the memory-allocated container that can host multiple workers.',
        ],
        'why_it_matters' => [
            'If this number reaches your "ServerLimit" setting, Apache will reject or queue new incoming connections, making your site appear down.',
        ],
        'how_to_read' => [
            'Compare this to your ServerLimit. It helps monitor the balance between capacity and memory usage.',
        ],
        'actions' => [
            [
                'case' => 'If the number of busy processes hits the maximum limit',
                'actions' => [
                    'Increase the "MaxRequestWorkers" in Apache configuration if RAM allows, or optimize code execution time.',
                ],
            ],
            [
                'case' => 'If processes stay busy even with very few visitors',
                'actions' => [
                    'Check for "zombie" processes or long-running scripts (like slow file downloads or heavy database queries) that keep slots occupied.',
                ],
            ],
        ],
        'good_to_know' => [
            'A process is "busy" as long as the connection is open. Slow client internet connections can keep a process busy even if your PHP code has finished its work.',
        ],
    ],
    Metric::ApacheMpm->value => [
        'definitions' => [
            'The MPM (Multi-Processing Module) is the core architecture Apache uses to handle network connections and requests.',
            'It determines whether Apache creates a new process for every visitor (Prefork) or uses a more modern, thread-based approach (Event/Worker).',
        ],
        'why_it_matters' => [
            'The MPM choice directly impacts your server\'s speed and RAM consumption. A modern MPM like "Event" allows handling thousands of visitors with very little memory.',
        ],
        'how_to_read' => [
            'This shows the current mode. "Event" is the modern standard, "Prefork" is the legacy mode (often used for older PHP compatibility).',
        ],
        'good_to_know' => [
            'If you are using PHP-FPM, you should almost always be using the "event" MPM for the best possible performance.',
        ],
    ],
    Metric::ApacheLoadAverage->value => [
        'definitions' => [
            'The load average is a system-level metric that represents the average number of processes using or waiting for the CPU over time (measured over 1, 5, and 15 minutes).',
            'This value is reported by Apache via its status module but reflects the overall server load, not Apache activity alone.',
        ],
        'why_it_matters' => [
            'It indicates the overall server pressure. A value of 1.0 means one CPU core is fully utilized.',
        ],
        'how_to_read' => [
            'Compare the values to your number of CPU cores. Load 4.0 on a 4-core machine means you are at 100% capacity.',
        ],
        'actions' => [
            [
                'case' => 'If the 1-minute load is temporarily higher than the others',
                'actions' => [
                    'This usually indicates a short traffic spike and is not necessarily a problem if it quickly returns to normal.',
                ],
            ],
            [
                'case' => 'If load is consistently higher than the core count',
                'actions' => ['Identify if the bottleneck is CPU, RAM, or slow Disk I/O.'],
            ],
        ],
        'good_to_know' => [
            'High load with low CPU usage usually means the server is waiting for slow disks (I/O Wait).',
        ],
    ],
    Metric::ApacheReqPerSec->value => [
        'definitions' => [
            'Requests per second (RPS) measures the throughput of your web server by counting how many HTTP requests are processed every second.',
            'A request can be anything from a full HTML page to a tiny CSS file, an image, or an API call.',
        ],
        'why_it_matters' => [
            'It is the primary indicator of your server\'s workload. Monitoring RPS helps you distinguish between a server that is slow because of traffic (high RPS) and a server that is slow because of a bug (low RPS but high load).',
        ],
        'how_to_read' => [
            'Compare the current RPS with your historical "normal" baseline. Sudden spikes often indicate bots or marketing success, while sudden drops suggest a network failure.',
        ],
        'actions' => [
            [
                'case' => 'If RPS spikes suddenly without a known cause',
                'actions' => [
                    'Check your access logs for suspicious patterns (DDoS, aggressive scrapers). Consider enabling a WAF or rate-limiting if the traffic is malicious.',
                ],
            ],
            [
                'case' => 'If RPS is high but your site feels slow',
                'actions' => [
                    'Offload static assets (images, JS) to a CDN or implement server-side caching (Opcache, Redis) to reduce the work required for each request.',
                ],
            ],
            [
                'case' => 'If RPS drops to zero',
                'actions' => [
                    'Check if Apache has crashed, if your SSL certificate has expired, or if a firewall/load balancer is blocking incoming traffic.',
                ],
            ],
        ],
        'good_to_know' => [
            'One user visiting a single page can trigger dozens of requests (images, scripts, fonts). Do not confuse "Requests per second" with "Users per second".',
        ],
    ],

    Metric::ApacheBytesPerSec->value => [
        'definitions' => [
            'Bytes per second measures the volume of data (bandwidth) sent by Apache to your visitors every second.',
            'It includes everything: HTML, heavy images, videos, and API payloads. It represents the "outgoing pipe" of your server.',
        ],
        'why_it_matters' => [
            'High bandwidth usage can slow down your site for everyone and significantly increase hosting costs. It helps detect if your site has become "heavy" or if someone is scraping your large files.',
        ],
        'how_to_read' => [
            'Look for a correlation with "Requests per second". If your traffic is stable but the bytes per second explode, your responses have likely become too heavy (e.g., an unoptimized 5MB image on the homepage).',
        ],
        'actions' => [
            [
                'case' => 'If bandwidth usage is high compared to the number of requests',
                'actions' => [
                    'Identify heavy assets (images, PDFs, videos) and optimize them. Ensure Gzip or Brotli compression is enabled in Apache to shrink text-based responses.',
                ],
            ],
            [
                'case' => 'If you notice unexplained bandwidth spikes',
                'actions' => [
                    'Check if your site is being "hotlinked" (other sites using your images) or if large files are being downloaded by bots. Consider using a CDN to offload this traffic.',
                ],
            ],
        ],
        'good_to_know' => [
            'Text (HTML/JS) is usually light, but one unoptimized image can consume as much bandwidth as 1,000 text requests. Always optimize your media first.',
        ],
    ],
    Metric::NginxVersion->value => [
        'definitions' => [
            'Nginx is a high-performance open-source web server, also widely used as a reverse proxy and load balancer.',
            'Endoflife timeline: <a href="https://endoflife.date/nginx" target="_blank">https://endoflife.date/nginx</a>.',
        ],
        'why_it_matters' => [
            'Running an outdated version exposes your server to security vulnerabilities. Modern versions also bring better support for protocols like HTTP/3 and improved caching performance.',
        ],
        'good_to_know' => [
            'Nginx is famous for its "hot upgrade" capability: you can usually update the software without dropping a single active client connection.',
        ],
    ],
    Metric::NginxActiveConnections->value => [
        'definitions' => [
            'Active connections is the total number of client connections currently open with Nginx.',
            'This includes users currently sending a request, those receiving a data response, and those staying connected between two requests.',
        ],
        'why_it_matters' => [
            'It helps you visualize the real-time "crowd" on your server. If this number exceeds your "worker_connections" limit, Nginx will start refusing new visitors.',
        ],
        'how_to_read' => [
            'Total connections = Reading + Writing + Waiting. A high "Waiting" count is usually normal and reflects active keep-alive sessions.',
        ],
        'actions' => [
            [
                'case' => 'If active connections spike suddenly',
                'actions' => [
                    'Identify if the traffic is legitimate or if a bot/attack is opening many connections without closing them (Slowloris type).',
                ],
            ],
            [
                'case' => 'If you frequently reach the connection limit',
                'actions' => [
                    'Increase "worker_connections" in your Nginx config or optimize the "keepalive_timeout" to release inactive connections faster.',
                ],
            ],
        ],
        'good_to_know' => [
            'A connection is not always an active request. Thanks to "Keep-Alive", a browser stays connected to your server to download multiple files faster, which increases the "Waiting" count but improves user experience.',
        ],
    ],

    Metric::NginxReqPerSec->value => [
        'definitions' => [
            'Requests per second (RPS) measures the number of HTTP requests processed by Nginx every second.',
            'It reflects the real-time workload of your web server, from serving static images to proxying requests to PHP.',
        ],
        'why_it_matters' => [
            'It is the best way to see your traffic peaks. Since Nginx is often the first entry point of your server, this metric is your early warning system for both viral success and DDoS attacks.',
        ],
        'how_to_read' => [
            'Monitor the "Requests" line. A sudden, massive jump often means a bot or a scraper is hitting your site, while a flat line at zero means your server is likely unreachable.',
        ],
        'actions' => [
            [
                'case' => 'If RPS spikes suddenly',
                'actions' => [
                    'Check your Nginx access logs to identify the source (IP, User-Agent). If it is malicious, use "deny" rules or a rate-limiting zone to protect your backend.',
                ],
            ],
            [
                'case' => 'If RPS is high but the backend (PHP/Database) is struggling',
                'actions' => [
                    'Leverage Nginx caching capabilities (FastCGI Cache or Proxy Cache) to serve content directly from Nginx without hitting PHP.',
                ],
            ],
        ],
        'good_to_know' => [
            'Nginx is built to handle an incredible amount of RPS (tens of thousands). If your server is slow at 10 RPS, the bottleneck is almost certainly in your PHP code or database, not Nginx.',
        ],
    ],
    Metric::NginxWaitingConnections->value => [
        'definitions' => [
            'Waiting connections are idle client connections that Nginx keeps open after a request is finished.',
            'Instead of closing the door, Nginx keeps it open so the same visitor can send their next request (like an image or a script) instantly without the overhead of reconnecting.',
        ],
        'why_it_matters' => [
            'It measures the efficiency of your "Keep-Alive" policy. High waiting connections improve user experience (faster page loading) but consume a small amount of memory and connection slots.',
        ],
        'how_to_read' => [
            'A high number is generally a good sign of modern browser behavior. However, if it exceeds your server\'s capacity, it might prevent new visitors from connecting.',
        ],
        'actions' => [
            [
                'case' => 'If waiting connections consume too many resources',
                'actions' => [
                    'Slightly decrease the "keepalive_timeout" in your Nginx configuration to release idle connections more quickly.',
                ],
            ],
            [
                'case' => 'If waiting connections are always zero',
                'actions' => [
                    'Check if Keep-Alive is disabled in your Nginx config or by a load balancer/firewall. This can make your site feel slower as every asset will require a new connection.',
                ],
            ],
        ],
        'good_to_know' => [
            'Think of a waiting connection like a waiter staying at your table after serving your drink, just in case you want to order food. It\'s faster for you, but the waiter isn\'t free for another table yet.',
        ],
    ],
    Metric::NginxActiveConnectionsRate->value => [
        'definitions' => [
            'This rate represents the percentage of available connection slots currently in use by Nginx.',
            'It compares your active traffic to the "worker_connections" limit defined in your configuration.',
        ],
        'why_it_matters' => [
            'If this rate hits 100%, Nginx is "full" and will flatly refuse any new visitor. Monitoring this percentage allows you to scale your configuration before a total lockout occurs.',
        ],
        'how_to_read' => [
            'Think of it as a saturation gauge. Below 70% is healthy. Above 80%, you are in the "warning" zone, especially if your traffic is still growing.',
        ],
        'actions' => [
            [
                'case' => 'If the rate consistently exceeds 80%',
                'actions' => [
                    'Increase the "worker_connections" value in your Nginx configuration. Check if your OS "ulimit" allows for more open files to support this increase.',
                ],
            ],
            [
                'case' => 'If the rate spikes suddenly to 100%',
                'actions' => [
                    'You are likely under a connection-flood attack or a massive traffic surge. Check logs and consider adding more Nginx nodes or a load balancer.',
                ],
            ],
        ],
        'good_to_know' => [
            'The total capacity of Nginx is (worker_processes × worker_connections). If you have a powerful multi-core server, you can handle hundreds of thousands of connections by tuning these two numbers.',
        ],
    ],
    Metric::NginxRefusedConnections->value => [
        'definitions' => [
            'Refused connections count how many times Nginx has flatly rejected a visitor because it had no more "room" to handle them.',
            'This happens when you hit the hard limits of your configuration or your operating system.',
        ],
        'why_it_matters' => [
            'Every refused connection is a lost customer or a failed service. This value should ideally always be zero. If it grows, your server is officially saturated and failing to fulfill its role.',
        ],
        'how_to_read' => [
            'Any value above zero is an emergency. It means your current traffic exceeds your server\'s configured capacity.',
        ],
        'actions' => [
            [
                'case' => 'If refused connections are > 0',
                'actions' => [
                    'Immediately increase "worker_connections" in your Nginx config. Also, check the Linux "backlog" settings (net.core.somaxconn) and "ulimit" to ensure the OS isn\'t the one blocking Nginx.',
                ],
            ],
            [
                'case' => 'If refused connections happen only during specific peaks',
                'actions' => [
                    'You are facing a capacity planning issue. Consider adding a load balancer or auto-scaling your Nginx nodes to absorb these spikes.',
                ],
            ],
        ],
        'good_to_know' => [
            'Sometimes, Nginx refuses connections not because it\'s slow, but because it\'s protecting itself from crashing. It\'s a safety valve, but one that tells you it\'s time to upgrade your infrastructure.',
        ],
    ],
    Metric::NginxReusedConnectionsRatio->value => [
        'definitions' => [
            'This ratio compares the number of HTTP requests to the number of actual TCP connections handled by Nginx.',
            'It measures the efficiency of the Keep-Alive mechanism: how many requests are served per single connection.',
        ],
        'why_it_matters' => [
            'Opening a new connection (especially with SSL/TLS) is resource-heavy and slow. A high ratio means your server is much faster for the user and consumes less CPU.',
        ],
        'how_to_read' => [
            'A ratio of 1 means every request requires a new connection (inefficient). A higher ratio (e.g., 10 or 50) means your server is successfully reusing connections.',
        ],
        'actions' => [
            [
                'case' => 'If the ratio is close to 1',
                'actions' => [
                    'Keep-Alive might be disabled or its timeout is too short. Increase "keepalive_requests" (the number of requests allowed per connection) and "keepalive_timeout" in your Nginx config.',
                ],
            ],
            [
                'case' => 'If the ratio drops suddenly',
                'actions' => [
                    'Check if a proxy or a Load Balancer in front of Nginx is cutting connections too early, forcing Nginx to renegotiate every request.',
                ],
            ],
        ],
        'good_to_know' => [
            'In modern web traffic, one page often contains 50+ assets. A good ratio saves your server from doing 50 "handshakes" with the same browser, which is a massive performance win.',
        ],
    ],
    Metric::CaddyVersion->value => [
        'definitions' => [
            'Caddy is a modern open-source web server with automatic HTTPS, often used as a reverse proxy or application server.',
            'Endoflife timeline: <a href="https://endoflife.date/caddy" target="_blank">https://endoflife.date/caddy</a>.',
        ],
        'why_it_matters' => [
            'Staying updated ensures you have the latest security patches and the most efficient implementation of modern protocols like HTTP/3 and QUIC, which Caddy supports natively.',
        ],
        'how_to_read' => [],
        'actions' => [],
        'good_to_know' => [
            'Caddy is written in Go, making it memory-safe by design. Updating it is generally very safe and guarantees that your automatic SSL certificates (via Let\'s Encrypt/ZeroSSL) continue to renew without issues.',
        ],
    ],
    Metric::CaddyMemoryUsage->value => [
        'definitions' => [
            'This metric measures the amount of RAM currently allocated to the Caddy process.',
            'Caddy is written in Go, which means it manages its own memory through a process called Garbage Collection.',
        ],
        'why_it_matters' => [
            'Monitoring memory helps ensure Caddy has enough "breathing room" to handle requests. Sudden growth can indicate that Caddy is buffering large files in RAM or struggling with a heavy configuration.',
        ],
        'how_to_read' => [
            'It is normal to see a "sawtooth" pattern: memory grows as requests come in, then drops suddenly when the Garbage Collector cleans it up.',
        ],
        'actions' => [
            [
                'case' => 'If memory usage never drops and keeps growing (linear growth)',
                'actions' => [
                    'This might be a memory leak or a misconfigured plugin. Check if you are serving very large files without streaming or if your log files are being buffered in memory.',
                ],
            ],
            [
                'case' => 'If Caddy is killed by the system (Out of Memory)',
                'actions' => [
                    'Increase the server RAM or limit Caddy\'s memory usage. Ensure you are not loading thousands of dynamic certificates or large JSON configurations simultaneously.',
                ],
            ],
        ],
        'good_to_know' => [
            'Unlike Apache, Caddy doesn\'t fork new processes for each request. This makes it very memory-efficient, but when it uses RAM, it usually does so to speed up things like TLS handshake caching or log processing.',
        ],
    ],
    Metric::CaddyCpuUsage->value => [
        'definitions' => [
            'This represents the percentage of a single CPU core capacity being used exclusively by the Caddy process.',
            'Unlike the system-wide CPU metric, this pinpoint exactly how much "brain power" Caddy needs to route your traffic.',
        ],
        'why_it_matters' => [
            'Caddy is notoriously efficient. If its CPU usage spikes, it\'s usually doing heavy lifting: either encrypting thousands of new SSL connections, compressing large assets (Gzip/Zstd), or proxying a massive amount of requests.',
        ],
        'how_to_read' => [
            'On a multi-core server, this value can exceed 100% (e.g., 250% means Caddy is fully utilizing 2.5 cores). Monitor this alongside "Requests per second" to see your server\'s cost-per-request.',
        ],
        'actions' => [
            [
                'case' => 'If Caddy CPU usage is consistently high (> 80% per core)',
                'actions' => [
                    'Enable or tune Caddy\'s caching headers to reduce proxying work. If you use heavy compression (like Zstd), consider lower levels or offloading it to a CDN.',
                ],
            ],
            [
                'case' => 'If CPU spikes without a traffic increase',
                'actions' => [
                    'Check for a high volume of new TLS handshakes (potential SSL flood attack) or complex Regex rules in your Caddyfile that might be inefficient.',
                ],
            ],
        ],
        'good_to_know' => [
            'Caddy\'s native support for HTTP/3 and QUIC can lead to slightly higher CPU usage than HTTP/2 because of the complex encryption involved, but it results in a much faster experience for your users.',
        ],
    ],
    Metric::CaddyReqPerSec->value => [
        'definitions' => [
            'Requests per second (RPS) measures the volume of traffic flowing through Caddy every second.',
            'It distinguishes between "PHP handler" (dynamic code execution) and "file_server" (direct delivery of images, JS, CSS).',
        ],
        'why_it_matters' => [
            'This breakdown allows you to see exactly what is consuming your resources. A spike in PHP requests is much more expensive for your CPU than a spike in static file requests.',
        ],
        'how_to_read' => [
            'Compare both lines: a healthy site usually serves many more static files than PHP requests. If PHP RPS is higher, your application might be missing a caching layer.',
        ],
        'actions' => [
            [
                'case' => 'If PHP requests are disproportionately high',
                'actions' => [
                    'Check if your frontend is making too many API calls or if your application is missing internal caching (like Redis). Every PHP request adds significant load to the server.',
                ],
            ],
            [
                'case' => 'If file_server requests dominate and impact performance',
                'actions' => [
                    'Offload these static assets to a CDN or ensure Caddy\'s "encode" directive (Gzip/Zstd) is active to reduce the bandwidth used per request.',
                ],
            ],
        ],
        'good_to_know' => [
            'Caddy\'s "file_server" is extremely fast. If your traffic grows, try to convert as many dynamic PHP requests as possible into static ones (via caching) to keep your server responsive.',
        ],
    ],
    Metric::CaddyAvgRequestDuration->value => [
        'definitions' => [
            'This is the average internal processing time for a request, from the moment Caddy receives it until the handler (PHP or file_server) finishes its job.',
            'It measures the "latency" of your server logic. It does not include the time it takes to send data over the internet to the client.',
        ],
        'why_it_matters' => [
            'A fast server should respond in milliseconds. If this duration increases, your users will feel the site "lagging," even if your network is fast. It is the best indicator of application performance degradation.',
        ],
        'how_to_read' => [
            'Static files (file_server) should be near-instant (1-5ms). PHP requests are naturally slower, but a sudden jump here usually means your database or an external API is slowing down.',
        ],
        'actions' => [
            [
                'case' => 'If PHP duration increases while CPU is low',
                'actions' => [
                    'The bottleneck is likely external: check for slow SQL queries, locked database tables, or slow responses from 3rd party APIs (payment gateways, social logins).',
                ],
            ],
            [
                'case' => 'If file_server duration increases',
                'actions' => [
                    'This is rare and usually means the server\'s disk (I/O) is saturated. Check if another process is heavily writing to the disk or if your storage is failing.',
                ],
            ],
        ],
        'good_to_know' => [
            'If this duration is high but your CPU/RAM metrics are low, your code is "waiting" for something (I/O, Database, Network). If it\'s high and CPU is also 100%, your code is "calculating" too much.',
        ],
    ],
    Metric::CaddyAvgResponseDuration->value => [
        'definitions' => [
            'This measures the time taken to stream the response data from Caddy to the visitor after the server has finished its internal processing.',
            'It reflects the "delivery phase": the speed at which bytes travel through the network to reach the client\'s browser.',
        ],
        'why_it_matters' => [
            'If this duration is high, your server is "fast" but your delivery is "slow". This usually points to heavy files (unoptimized images), a lack of compression, or a poor network connection between the server and the user.',
        ],
        'how_to_read' => [
            'Compare this with "Request Duration". If Request is low but Response is high, don\'t touch your code: the issue is the size of your assets or your bandwidth limits.',
        ],
        'actions' => [
            [
                'case' => 'If Response Duration is high for static files',
                'actions' => [
                    'The files are likely too heavy. Optimize images, minify CSS/JS, and ensure Caddy is using "encode zstd gzip" to shrink the data being sent.',
                ],
            ],
            [
                'case' => 'If Response Duration is high for PHP (dynamic) content',
                'actions' => [
                    'Your application might be generating massive HTML pages or large JSON payloads. Check if you can paginate your API results or simplify your templates.',
                ],
            ],
            [
                'case' => 'If Response Duration spikes for all handlers',
                'actions' => [
                    'The server\'s outgoing network (bandwidth) might be saturated. Check if another process is performing a large backup or if you are facing a massive traffic surge.',
                ],
            ],
        ],
        'good_to_know' => [
            'A slow client (like someone on a poor 3G connection) can inflate this metric because Caddy has to wait for the client to acknowledge receiving the data packets.',
        ],
    ],
    Metric::CaddyRequestsUnder250->value => [
        'definitions' => [
            'This represents the percentage of all requests completed in under 250 milliseconds.',
            'It measures the "snappiness" of your site: the proportion of visitors who experience a near-instant response.',
        ],
        'why_it_matters' => [
            'Averages can hide issues (the "flaw of averages"). This metric ensures that the vast majority of your users are getting a fast experience, which is critical for SEO and conversion rates.',
        ],
        'how_to_read' => [
            'Target 95% or higher. If this percentage drops, it means a growing portion of your users is starting to perceive your site as "slow" or "laggy".',
        ],
        'actions' => [
            [
                'case' => 'If this percentage drops while average duration stays stable',
                'actions' => [
                    'You likely have "outliers": specific pages or API endpoints that have become very slow. Identify these slow routes in your logs to fix them specifically.',
                ],
            ],
            [
                'case' => 'If the percentage drops during high traffic',
                'actions' => [
                    'Your server is likely struggling to keep up with concurrent tasks. Consider increasing your PHP-FPM pool size or adding more CPU cores to maintain the 250ms threshold.',
                ],
            ],
        ],
        'good_to_know' => [
            '250ms is often considered the limit for a "fast" feel. Beyond this, the human brain begins to perceive a slight delay. Maintaining a high score here is better for your brand than just having a good average.',
        ],
    ],
    Metric::CaddyRequestSize->value => [
        'definitions' => [
            'This metric measures the volume of data sent by visitors to your server (inbound traffic) every second.',
            'It includes everything from small URL headers to large file uploads or heavy JSON payloads sent to your API.',
        ],
        'why_it_matters' => [
            'While "Response Size" is about what you send, "Request Size" is about what you receive. Monitoring this helps detect large file uploads that might saturate your disk or "request body" attacks (DDoS) designed to slow down the server.',
        ],
        'how_to_read' => [
            'Normally, "file_server" inbound traffic is very low (just small URL requests). If "PHP handler" inbound traffic spikes, someone is likely uploading data or sending large batches of information to your application.',
        ],
        'actions' => [
            [
                'case' => 'If you see a spike in PHP inbound traffic',
                'actions' => [
                    'Check your logs for file upload activity or large POST requests. If it\'s unexpected, verify your "request_body" size limits in Caddy to prevent memory exhaustion.',
                ],
            ],
            [
                'case' => 'If inbound traffic is high but requests are low',
                'actions' => [
                    'This means a few users are sending very large amounts of data. Ensure your server has enough disk space for temporary upload storage and that PHP-FPM limits are correctly configured.',
                ],
            ],
        ],
        'good_to_know' => [
            'Most web traffic is "asymmetric": we receive a tiny request (a few bytes) and send a large response (kilobytes or megabytes). If your inbound traffic matches or exceeds your outbound, you are likely running an upload-heavy service or a data-processing API.',
        ],
    ],
    Metric::CaddyResponseSize->value => [
        'definitions' => [
            'This metric measures the total volume of data sent by Caddy to your visitors (outbound traffic) every second.',
            'It breaks down into "PHP handler" for dynamic pages/APIs and "file_server" for static assets like images and videos.',
        ],
        'why_it_matters' => [
            'It is your primary bandwidth indicator. High values here directly impact your hosting costs and can slow down the site for everyone if the server\'s network port reaches its physical limit.',
        ],
        'how_to_read' => [
            'A healthy balance shows static traffic dominating dynamic traffic. If your PHP response size is huge, you are likely sending unoptimized data (JSON/HTML) that could be cached or compressed.',
        ],
        'actions' => [
            [
                'case' => 'If the file_server response size is very high',
                'actions' => [
                    'Identify heavy assets (4K images, videos). Use Caddy\'s "encode" directive to enable Zstd or Gzip compression, and consider a CDN to serve these files closer to your users.',
                ],
            ],
            [
                'case' => 'If PHP response size is higher than expected',
                'actions' => [
                    'Your application might be returning massive JSON arrays or unminified HTML. Implement pagination for API results and check if Gzip compression is active for dynamic content.',
                ],
            ],
        ],
        'good_to_know' => [
            'Bandwidth is often billed by the GB. Lowering your Response Size through compression (Zstd/Gzip) doesn\'t just make the site faster for users—it directly saves you money on your infrastructure bill.',
        ],
    ],

    Metric::FrankenPhpVersion->value => [
        'definitions' => [
            'FrankenPHP is a modern PHP application server built on top of Caddy, designed to run PHP applications with high performance and worker mode support.',
            'FrankenPHP releases: <a href="https://github.com/dunglas/frankenphp/releases" target="_blank">https://github.com/dunglas/frankenphp/releases</a>.',
            'Only the latest version is supported, and new versions are released "when ready".',
        ],
        'why_it_matters' => ['Important for performance features like "Worker Mode".'],
        'how_to_read' => [],
        'actions' => [],
        'good_to_know' => [
            'FrankenPHP allows PHP to stay in memory between requests, making it significantly faster.',
        ],
    ],
    Metric::CaddyAvgRequestSize->value => [
        'definitions' => [
            'This metric calculates the average size (in bytes) of incoming HTTP requests sent by your visitors.',
            'It includes everything the client sends to you: URL, headers, cookies, and the request body (like form data or file uploads).',
        ],
        'why_it_matters' => [
            'A sudden increase in average size often signals that users are uploading larger files or that your API is receiving heavier payloads. It\'s a key metric for capacity planning and security monitoring.',
        ],
        'how_to_read' => [
            'For a standard website, this should be very low (a few KB). If the "PHP handler" average is high, it confirms that your application is processing data-heavy inputs.',
        ],
        'actions' => [
            [
                'case' => 'If the average request size for PHP increases significantly',
                'actions' => [
                    'Check if you have recently enabled new upload features or if an API is receiving massive JSON/XML payloads. Ensure your "request_body" limits are tight enough to prevent abuse.',
                ],
            ],
            [
                'case' => 'If the file_server average size is unusually high',
                'actions' => [
                    'This is abnormal. It may indicate that clients are sending massive headers or large amounts of unnecessary cookie data. Check your logs for "431 Request Header Fields Too Large" errors.',
                ],
            ],
        ],
        'good_to_know' => [
            'Cookies are part of the request size! If you use many tracking scripts or store large amounts of data in cookies, every single request from that user becomes heavier, slowing down their connection.',
        ],
    ],
    Metric::CaddyAvgResponseSize->value => [
        'definitions' => [
            'This metric calculates the average size (in bytes) of the responses Caddy sends back to your visitors.',
            'It helps distinguish the average weight of your dynamic pages (PHP) versus your static assets (file_server).',
        ],
        'why_it_matters' => [
            'The larger the response, the longer it takes to travel over the network. High average sizes lead to slower "Page Load" times, especially on mobile devices, and increase your total bandwidth consumption.',
        ],
        'how_to_read' => [
            'A jump in this value without a change in traffic often means a new heavy asset (like an unoptimized banner) or a database query returning too much data has been deployed.',
        ],
        'actions' => [
            [
                'case' => 'If the average PHP response size is high (> 100-200 KB)',
                'actions' => [
                    'Review your API and page outputs. Implement pagination for lists, remove unnecessary data from JSON responses, and verify that Caddy is compressing these dynamic flows.',
                ],
            ],
            [
                'case' => 'If the file_server average size spikes',
                'actions' => [
                    'You are likely serving large unoptimized images or PDF files. Use modern formats (WebP/Avif for images) and ensure the "encode" directive is active in your Caddyfile.',
                ],
            ],
        ],
        'good_to_know' => [
            'A high average response size is the #1 enemy of Core Web Vitals (LCP). By simply enabling Zstd compression in Caddy, you can often reduce this metric by 70% for text-based content (HTML/JSON).',
        ],
    ],
    Metric::FrankenPhpVersion->value => [
        'definitions' => [
            'FrankenPHP is a modern PHP application server built on top of Caddy, designed to run PHP applications with high performance and worker mode support.',
            'FrankenPHP releases: <a href="https://github.com/dunglas/frankenphp/releases" target="_blank">https://github.com/dunglas/frankenphp/releases</a>.',
            'Only the latest version is supported, and new versions are released "when ready".',
        ],
        'why_it_matters' => [
            'Running an outdated version may miss important bug fixes, security patches, or performance improvements',
        ],
        'how_to_read' => [
            'Ensure your version matches the latest stable release. FrankenPHP moves fast, and older versions may lack support for newer PHP features or critical performance patches.',
        ],
        'good_to_know' => [
            'FrankenPHP eliminates the need for a separate PHP-FPM process. By running everything in a single binary, it simplifies your infrastructure while providing features like early hints and automatic HTTPS natively.',
        ],
    ],
    Metric::FrankenPhpBusyThreadsPercent->value => [
        'definitions' => [
            'This metric shows the percentage of available PHP threads currently busy executing your code.',
            'FrankenPHP uses a high-performance thread pool; this percentage measures how much of that "brain power" is currently occupied.',
        ],
        'why_it_matters' => [
            'It is your primary indicator of PHP saturation. If this reaches 100%, any new visitor will be placed in a queue, significantly increasing wait times (latency) or causing "504 Gateway Timeout" errors.',
        ],
        'how_to_read' => [
            'A healthy server typically sits between 20% and 60%. If you are consistently above 80%, you have no margin for traffic spikes.',
        ],
        'actions' => [
            [
                'case' => 'If the percentage is consistently above 80%',
                'actions' => [
                    'Increase the number of threads/workers in your FrankenPHP configuration. If your CPU usage is also high, you may need to upgrade to a server with more cores.',
                ],
            ],
            [
                'case' => 'If the percentage spikes to 100% while traffic is normal',
                'actions' => [
                    'An external resource (like a database or an API) is likely slow. Your PHP threads are "stuck" waiting for a response, preventing them from handling other requests.',
                ],
            ],
        ],
        'good_to_know' => [
            'Because FrankenPHP runs PHP in-process, it doesn\'t have the overhead of starting new processes like PHP-FPM. This means 100% thread usage in FrankenPHP is often more efficient than 100% in FPM, but it\'s still a limit you don\'t want to hit.',
        ],
    ],
    Metric::FrankenPhpBusyThreads->value => [
        'definitions' => [
            'This metric counts the exact number of PHP threads currently executing a request.',
            'Each "busy" thread represents one active visitor or background task being processed by PHP at this very instant.',
        ],
        'why_it_matters' => [
            'It allows you to visualize the real concurrency of your application. If you have 10 busy threads, it means 10 PHP scripts are running in parallel. This is the most direct way to see the impact of your traffic on PHP.',
        ],
        'how_to_read' => [
            'Compare this number to your total configured threads. If you have 50 threads and you are constantly at 45, you are flirting with the limit. If it\'s always at 1 or 2, your server is currently over-dimensioned.',
        ],
        'actions' => [
            [
                'case' => 'If the number of busy threads is high even with low traffic',
                'actions' => [
                    'Your scripts are taking too long to finish. This is usually due to "blocking" operations: slow database queries, long file writes, or waiting for external API responses.',
                ],
            ],
            [
                'case' => 'If the number of busy threads is consistently at your "max_threads" limit',
                'actions' => [
                    'You are bottlenecked. Increase the thread count in your FrankenPHP config if you have enough CPU/RAM, or investigate why your application has become so concurrent.',
                ],
            ],
        ],
        'good_to_know' => [
            'In Worker Mode, FrankenPHP keeps these threads "warm" (already booted), which makes them much faster than traditional PHP. However, each thread still consumes memory; finding the "sweet spot" between too many and too few threads is key to a stable server.',
        ],
    ],
    Metric::FrankenPhpWorkerBusyWorkersRate->value => [
        'definitions' => [
            'This represents the occupancy rate of your PHP Workers. Workers are long-lived processes that stay "alive" between requests to eliminate PHP startup overhead.',
            'It specifically monitors the pool dedicated to Worker Mode, which is separate from the standard request handling.',
        ],
        'why_it_matters' => [
            'The Worker Mode is designed for maximum speed. If this rate reaches 100%, your high-performance pool is saturated. New requests will either fall back to slower standard threads or be queued, losing the benefits of the Worker Mode.',
        ],
        'how_to_read' => [
            'Think of this as your "Elite Squad" availability. You want this rate to stay under 80% to ensure that every incoming request can be picked up instantly by a warm worker.',
        ],
        'actions' => [
            [
                'case' => 'If the rate is consistently near 100%',
                'actions' => [
                    'Increase the number of workers in your configuration. However, watch your memory usage: each worker keeps your entire application (and its memory leaks, if any) in RAM.',
                ],
            ],
            [
                'case' => 'If the rate is high but CPU usage is low',
                'actions' => [
                    'Your workers are likely waiting for an external service (Database, Redis, API). Even if they are just "waiting," a busy worker cannot take another request until it\'s finished.',
                ],
            ],
        ],
        'good_to_know' => [
            'Worker Mode is like keeping a car engine running at a red light. It\'s ready to go instantly (no cold start), but it consumes "fuel" (RAM) even when idle. Tuning the number of workers is a balance between speed and memory.',
        ],
    ],
    Metric::FrankenPhpWorkerRequestPerSec->value => [
        'definitions' => [
            'This measures the number of requests per second (RPS) being handled specifically by your high-performance Worker pool.',
            'It represents the "speed limit" of your application when running in its most optimized state.',
        ],
        'why_it_matters' => [
            'Monitoring Worker RPS helps you understand the return on investment of using Worker Mode. A steady or increasing RPS with low latency confirms that your application is scaling efficiently without the overhead of PHP process creation.',
        ],
        'how_to_read' => [
            'Compare this to your total Caddy RPS. If most of your traffic is dynamic but the Worker RPS is low, it means your requests might be falling back to standard threads, missing out on the Worker Mode performance.',
        ],
        'actions' => [
            [
                'case' => 'If Worker RPS drops while total traffic is stable',
                'actions' => [
                    'A worker might have crashed or restarted due to a memory limit. Check your logs for "worker exited" messages. This often happens if your code has a memory leak that eventually kills the worker.',
                ],
            ],
            [
                'case' => 'If Worker RPS is high but latency (response time) is also increasing',
                'actions' => [
                    'Your workers are processing a lot of traffic, but they are reaching their throughput limit. It is time to add more workers to the pool or optimize your most called functions.',
                ],
            ],
        ],
        'good_to_know' => [
            'FrankenPHP workers are incredibly fast, but they are also persistent. If you update your code, you MUST restart the workers to see the changes, otherwise, they will continue to serve the old version of your application from memory.',
        ],
    ],
    Metric::FrankenPhpWorkerPhpExecTime->value => [
        'definitions' => [
            'This measures the average time (in milliseconds) the PHP engine spends executing your script logic within the Worker pool.',
            'It represents the "pure" processing time of your application code, excluding the web server overhead.',
        ],
        'why_it_matters' => [
            'In Worker Mode, you\'ve already eliminated the startup time (boot). If this metric is high, it means the logic itself is slow. High execution time ties up your workers, preventing them from taking new requests and drastically reducing your server\'s maximum capacity.',
        ],
        'how_to_read' => [
            'Compare this with your non-worker PHP execution time. It should be significantly lower. A sudden increase usually points to a slow database query or a synchronous external API call that is blocking the worker.',
        ],
        'actions' => [
            [
                'case' => 'If execution time increases after a deployment',
                'actions' => [
                    'You may have introduced a heavy loop or a missing database index. Since workers are persistent, check if a memory leak or a growing internal state is making each subsequent execution slower.',
                ],
            ],
            [
                'case' => 'If execution time is high but CPU usage is low',
                'actions' => [
                    'Your code is "waiting" rather than "working." Profile your external dependencies: slow SQL, slow Redis, or slow 3rd-party HTTP requests are the usual culprits.',
                ],
            ],
        ],
        'good_to_know' => [
            'One of the biggest advantages of FrankenPHP is that it can handle "Early Hints" while PHP is still executing. If your execution time is naturally long, using Early Hints can help the browser start loading assets while the worker is still finishing the PHP logic.',
        ],
    ],
    Metric::FrankenPhpWorkerReadyWorkers->value => [
        'definitions' => [
            'This represents the number of PHP Workers that have finished their boot sequence and are actively standing by to handle requests.',
            'A worker is "Ready" only when its internal state is fully loaded (e.g., Laravel/Symfony container) and it is capable of processing traffic instantly.',
        ],
        'why_it_matters' => [
            'If you have 10 workers configured but only 2 are "Ready", your server will perform like a much smaller machine. This metric is vital to detect "zombie" workers or infinite boot loops where workers start but never actually become operational.',
        ],
        'how_to_read' => [
            'This should be a flat line equal to your configured worker count. If this line "wiggles" or stays below your config, your application is struggling to maintain its worker pool.',
        ],
        'actions' => [
            [
                'case' => 'If Ready Workers is consistently below your configured count',
                'actions' => [
                    'Your application might be failing during the boot phase. Check for fatal errors in your initialization scripts or issues with environment variables that only appear in Worker Mode.',
                ],
            ],
            [
                'case' => 'If this number frequently drops to zero and then recovers',
                'actions' => [
                    'You are likely experiencing "Worker Flapping". This happens if you have a very low "max_requests" setting or if your workers are crashing due to segmentation faults or memory exhaustion.',
                ],
            ],
        ],
        'good_to_know' => [
            'The time it takes for a worker to become "Ready" is the only time you ever pay for a cold start in Worker Mode. Once it\'s ready, it stays ready until the process is explicitly killed or restarted.',
        ],
    ],
    Metric::FrankenPhpWorkerWorkerRestarts->value => [
        'definitions' => [
            'This counts the total number of times your PHP Workers have been killed and replaced since FrankenPHP started.',
            'While some restarts are normal (deployments), a high frequency usually points to application crashes or memory leaks.',
        ],
        'why_it_matters' => [
            'In Worker Mode, the goal is stability. Frequent restarts force the worker to perform a "cold start" (reloading the framework), which slows down requests and consumes CPU. If restarts are constant, you lose all the performance benefits of FrankenPHP.',
        ],
        'how_to_read' => [
            'In a perfect world, this should be a flat line that only jumps during your deployments. A "staircase" pattern (regular increases) is a sign of a recurring issue.',
        ],
        'actions' => [
            [
                'case' => 'If the restart count increases regularly (outside of deployments)',
                'actions' => [
                    'Check for "Segmentation Faults" or "Out of Memory" (OOM) errors in your system logs. Your PHP code might be triggering a fatal error that the worker cannot recover from.',
                ],
            ],
            [
                'case' => 'If restarts happen every X requests',
                'actions' => [
                    'Verify your "MAX_REQUESTS" environment variable. If set too low, FrankenPHP will intentionally kill workers to prevent memory leaks, but this adds unnecessary overhead.',
                ],
            ],
        ],
        'good_to_know' => [
            'Worker Mode is sensitive. A simple "exit()" or "die()" in your PHP code will kill the entire worker thread. Ensure your application handles errors gracefully with exceptions instead of terminating the script.',
        ],
    ],
    Metric::PhpVersion->value => [
        'definitions' => [
            'PHP is the server-side scripting language used to run your application.',
            'Official timeline: <a href="https://www.php.net/supported-versions.php" target="_blank">https://www.php.net/supported-versions.php</a>.',
            'Endoflife timeline: <a href="https://endoflife.date/php" target="_blank">https://endoflife.date/php</a>.',
        ],
        'why_it_matters' => ['Performance and security. Older versions (like 7.x) are no longer supported.'],
        'good_to_know' => [
            'Each PHP version is supported for exactly 3 years. Once "End of Life", it no longer receives security patches.',
        ],
    ],
    Metric::PostgresVersion->value => [
        'definitions' => [
            'PostgreSQL is a powerful, open-source object-relational database system known for its reliability, data integrity, and standards compliance.',
            'Endoflife timeline: <a href="https://endoflife.date/postgresql" target="_blank">https://endoflife.date/postgresql</a>.',
        ],
        'why_it_matters' => [
            'The database holds the most critical part of your stack. Running an EOL (End of Life) version means no more security patches or bug fixes, exposing your data to known vulnerabilities. Each major release also brings significant gains in performance (query planner, parallelism, indexing) and new features.',
        ],
        'good_to_know' => [
            'PostgreSQL ships one major version per year, and each major version is supported for 5 years after its initial release. A major upgrade requires a migration step (pg_upgrade or dump/restore), so plan it ahead rather than waiting for the EOL deadline.',
        ],
    ],
    Metric::PostgresCapacity->value => [
        'definitions' => [
            'Shared buffers is the amount of memory PostgreSQL dedicates to caching table and index data in RAM (the "shared_buffers" parameter).',
            'It is the database\'s primary cache: data found here is served directly from memory, without touching the disk.',
        ],
        'why_it_matters' => [
            'Reading from RAM is orders of magnitude faster than reading from disk. A well-sized shared buffers keeps your hot data in memory, drastically reducing disk I/O and speeding up queries.',
        ],
        'how_to_read' => [
            'The value is shown as a memory size (e.g. 128 MiB). The default of 128 MiB is very conservative and is rarely optimal for a dedicated database server.',
        ],
        'actions' => [
            [
                'case' => 'If the value is still at the default (128 MiB) on a dedicated server',
                'actions' => [
                    'A common starting point is to set "shared_buffers" to around 25% of the total system RAM. Going much higher rarely helps, because PostgreSQL also relies on the operating system\'s file cache.',
                ],
            ],
        ],
        'good_to_know' => [
            'Unlike some databases, PostgreSQL intentionally keeps "shared_buffers" moderate and leans on the OS page cache as a second caching layer. That is why allocating 80% of the RAM here is usually counter-productive. Changing this value requires a server restart.',
        ],
    ],
    Metric::PostgresConnectionsUsage->value => [
        'definitions' => [
            'This represents the percentage of the connection slots currently in use.',
            'It compares the number of active backend connections to the "max_connections" limit defined in your PostgreSQL configuration.',
        ],
        'why_it_matters' => [
            'In PostgreSQL, every connection is a separate operating-system process, which makes connections relatively expensive in both memory and CPU. If you reach "max_connections", PostgreSQL rejects any new connection, causing errors across your application until slots free up.',
        ],
        'how_to_read' => [
            'A healthy usage stays below 70%. Frequent spikes above 80% put you in the "danger zone", where a small traffic surge can exhaust the available slots and start rejecting connections.',
        ],
        'actions' => [
            [
                'case' => 'If usage is consistently high (> 80%)',
                'actions' => [
                    'Put a connection pooler such as PgBouncer in front of PostgreSQL. It is the standard solution and lets hundreds of application clients share a small number of real database connections.',
                ],
            ],
            [
                'case' => 'If usage spikes suddenly to 100%',
                'actions' => [
                    'Look for connection leaks (clients that open connections without closing them) and for long-running or idle-in-transaction sessions that keep slots occupied.',
                ],
            ],
        ],
        'good_to_know' => [
            'Simply raising "max_connections" is rarely the right fix: each extra connection consumes RAM (work_mem, process overhead), so a high limit on a small server can trade a "too many connections" error for an out-of-memory crash. A pooler is almost always the better answer.',
        ],
    ],
    Metric::PostgresConnectionsStates->value => [
        'definitions' => [
            'This chart breaks down your backend connections by state over time: Active, Idle, and Idle in transaction.',
        ],
        'why_it_matters' => [
            'The mix of states tells you how connections are actually being used. A healthy database shows brief bursts of "Active" connections, while a growing number of "Idle in transaction" sessions is a classic sign of application bugs that can block maintenance and lock other queries.',
        ],
        'how_to_read' => [
            '<strong>Active</strong>: the connection is currently executing a query. A high count means real work, but a sustained high level can indicate slow queries or saturation.',
            '<strong>Idle</strong>: the connection is open but doing nothing, with no transaction in progress. This is normal, especially with a connection pool that keeps connections ready for reuse.',
            '<strong>Idle in transaction</strong>: the connection has opened a transaction (BEGIN) but is sitting idle without committing or rolling back. This is the one to watch closely.',
        ],
        'actions' => [
            [
                'case' => 'If "Idle in transaction" keeps growing',
                'actions' => [
                    'Find application code that opens a transaction and then waits (network calls, slow processing) before committing. These sessions hold locks and prevent autovacuum from cleaning up dead rows, hurting the whole database.',
                ],
            ],
            [
                'case' => 'If "Active" stays high for long periods',
                'actions' => [
                    'Investigate slow or concurrent queries. Check the slow-query metrics and consider indexing or query optimization, as the database may be struggling to keep up with the load.',
                ],
            ],
        ],
        'good_to_know' => [
            'You can automatically clean up stuck sessions by setting "idle_in_transaction_session_timeout", which forces PostgreSQL to terminate transactions that stay idle for too long.',
        ],
    ],
    Metric::PostgresTransactionsPerSec->value => [
        'definitions' => [
            'This chart shows the rate of transactions processed per second, split into Commits (successful transactions) and Rollbacks (cancelled transactions).',
            'It is derived from the cumulative "xact_commit" and "xact_rollback" counters of your database.',
        ],
        'why_it_matters' => [
            'Transactions per second is a core throughput indicator: it tells you how much work your database is actually doing. Tracking it helps you correlate database load with application traffic and spot abnormal activity.',
        ],
        'how_to_read' => [
            '<strong>Commits</strong>: transactions that completed successfully. This is the bulk of healthy activity and should follow your traffic patterns.',
            '<strong>Rollbacks</strong>: transactions that were cancelled (errors, deadlocks, or explicit ROLLBACK). A few are normal, but a high or rising share usually points to application errors or failing queries.',
        ],
        'actions' => [
            [
                'case' => 'If rollbacks represent a significant share of transactions',
                'actions' => [
                    'Check the "Rollback ratio" metric and your application logs for failing queries, deadlocks, or constraint violations. Frequent rollbacks waste resources and often hide real bugs.',
                ],
            ],
            [
                'case' => 'If throughput drops unexpectedly',
                'actions' => [
                    'A sudden fall while traffic stays constant can indicate locking, slow queries, or a saturated server. Cross-check with connection states and slow-query metrics.',
                ],
            ],
        ],
        'good_to_know' => [
            'Read-only queries that run outside an explicit transaction still count as transactions here, so even a mostly-read workload will show a steady commit rate.',
        ],
    ],
    Metric::PostgresRollbackRatio->value => [
        'definitions' => [
            'This is the percentage of transactions that ended in a rollback rather than a commit.',
            'It is computed from the "xact_rollback" and "xact_commit" counters: rollbacks / (commits + rollbacks).',
        ],
        'why_it_matters' => [
            'Rollbacks mean work was done and then thrown away. A high ratio wastes CPU, I/O, and connection time, and usually signals application errors, failing queries, or deadlocks rather than normal behavior.',
        ],
        'how_to_read' => [
            'A healthy database keeps this ratio low, typically a few percent. A consistently high ratio (e.g. above 5–10%) is worth investigating, as it indicates that a meaningful share of your transactions never complete successfully.',
        ],
        'actions' => [
            [
                'case' => 'If the ratio is consistently high',
                'actions' => [
                    'Inspect your application logs for failing queries, constraint violations, or deadlocks. Look for code paths that explicitly issue ROLLBACK, and for retries that abort transactions.',
                ],
            ],
            [
                'case' => 'If the ratio spikes suddenly',
                'actions' => [
                    'Correlate the spike with a deploy or a traffic change. A new release introducing buggy queries or lock contention is a common cause of a sudden rise in rollbacks.',
                ],
            ],
        ],
        'good_to_know' => [
            'Some frameworks and health checks intentionally roll back transactions (for example to test connectivity without writing data), which can inflate this ratio without indicating a real problem. Know your application\'s baseline before raising an alert.',
        ],
    ],
    Metric::PostgresCacheHitRatio->value => [
        'definitions' => [
            'This is the percentage of data block reads served from PostgreSQL\'s memory cache (shared buffers) instead of from disk.',
            'It is computed from the "blks_hit" and "blks_read" counters: hits / (hits + reads).',
        ],
        'why_it_matters' => [
            'Serving data from RAM is dramatically faster than reading from disk. A high cache hit ratio means most queries avoid disk I/O, which is one of the biggest factors in overall database responsiveness.',
        ],
        'how_to_read' => [
            'On a warmed-up database, this ratio should be very high, typically above 99%. A value that drops below ~95% suggests that queries are frequently hitting the disk, which slows them down.',
        ],
        'actions' => [
            [
                'case' => 'If the ratio is consistently low (< 95%)',
                'actions' => [
                    'Your working set may not fit in memory. Consider increasing "shared_buffers" (see the Shared buffers card) or adding more RAM to the server so that hot data stays cached.',
                ],
            ],
            [
                'case' => 'If the ratio drops occasionally',
                'actions' => [
                    'Look for large scans or analytical queries that read big tables and evict hot data from the cache. Better indexing can avoid full scans and keep the cache effective.',
                ],
            ],
        ],
        'good_to_know' => [
            'This ratio only measures PostgreSQL\'s own buffer cache; the operating system also caches files, so the real disk-read rate is even lower than this number suggests. A freshly restarted database will show a low ratio until its cache warms up, which is normal.',
        ],
    ],
    Metric::PostgresDiskReadsPerSec->value => [
        'definitions' => [
            'This chart shows the rate of data blocks PostgreSQL had to read from disk per second, because they were not found in the memory cache.',
            'It is the per-second derivative of the "blks_read" counter.',
        ],
        'why_it_matters' => [
            'Disk reads are far slower than memory reads. This metric is the concrete counterpart of the cache hit ratio: it shows the actual volume of "cache misses" that forced PostgreSQL to go to disk, which directly impacts query latency.',
        ],
        'how_to_read' => [
            'A low and stable line is healthy: most data is being served from cache. Sustained high values, or spikes that line up with slow response times, mean queries are frequently hitting the disk.',
        ],
        'actions' => [
            [
                'case' => 'If disk reads are consistently high',
                'actions' => [
                    'Your hot data likely does not fit in memory. Increase "shared_buffers" or add RAM, and review whether large sequential scans could be avoided with better indexes.',
                ],
            ],
            [
                'case' => 'If you see sharp spikes',
                'actions' => [
                    'Look for heavy analytical queries, batch jobs, or reports that scan large tables. Scheduling them off-peak or optimizing them reduces the pressure on disk I/O.',
                ],
            ],
        ],
        'good_to_know' => [
            'A "disk read" here means the block was not in PostgreSQL\'s shared buffers; it may still have been served from the operating system\'s file cache rather than the physical disk. Expect this metric to rise right after a restart, while the cache warms up.',
        ],
    ],
    Metric::PostgresTempFiles->value => [
        'definitions' => [
            'This chart shows the rate of temporary files PostgreSQL created on disk to handle operations that did not fit in memory.',
            'It is the per-second derivative of the "temp_files" counter.',
        ],
        'why_it_matters' => [
            'When a sort, hash, or join needs more memory than "work_mem" allows, PostgreSQL spills the data to temporary files on disk. This is much slower than working in RAM, so a steady stream of temp files is a clear sign that some queries are memory-starved.',
        ],
        'how_to_read' => [
            'Ideally this line stays at or near zero. Occasional temp files during heavy reports are acceptable, but a constant or growing rate means queries are regularly spilling to disk and running slower than they could.',
        ],
        'actions' => [
            [
                'case' => 'If temp files are created frequently',
                'actions' => [
                    'Identify the queries doing large sorts or hashes (big ORDER BY, GROUP BY, DISTINCT, or joins). Increasing "work_mem" lets them run in memory, but raise it carefully since it applies per operation and can multiply across concurrent queries.',
                ],
            ],
            [
                'case' => 'If only a few queries are responsible',
                'actions' => [
                    'Rather than raising "work_mem" globally, optimize those queries (add indexes, reduce the rows being sorted) or set a higher "work_mem" locally for just that session or transaction.',
                ],
            ],
        ],
        'good_to_know' => [
            'Because "work_mem" is allocated per sort/hash operation and per connection, a single complex query can use several multiples of it at once. This is why blindly increasing it can lead to out-of-memory situations under load.',
        ],
    ],
    Metric::PostgresRowsWrittenPerSec->value => [
        'definitions' => [
            'This chart shows the rate of rows written per second, split into Inserted, Updated, and Deleted.',
            'It is derived from the "tup_inserted", "tup_updated", and "tup_deleted" counters.',
        ],
        'why_it_matters' => [
            'This is your write workload at the row level. Beyond raw throughput, it reveals how much churn your tables go through: in PostgreSQL, every UPDATE and DELETE leaves behind a "dead" row version that autovacuum must later clean up.',
        ],
        'how_to_read' => [
            '<strong>Inserted</strong>: new rows added. Pure inserts do not create dead rows.',
            '<strong>Updated</strong>: each update creates a new row version and marks the old one dead, generating maintenance work.',
            '<strong>Deleted</strong>: deleted rows also become dead versions until autovacuum reclaims them.',
        ],
        'actions' => [
            [
                'case' => 'If updates and deletes are very high',
                'actions' => [
                    'Expect significant dead-tuple churn. Make sure autovacuum keeps up (check the Dead tuple ratio metric); for very write-heavy tables, consider more aggressive per-table autovacuum settings.',
                ],
            ],
            [
                'case' => 'If write rates are higher than expected',
                'actions' => [
                    'Look for chatty application patterns: row-by-row writes in loops, redundant updates that do not change values, or missing batching. Reducing unnecessary writes lowers both load and bloat.',
                ],
            ],
        ],
        'good_to_know' => [
            'A frequently updated row can be optimized via HOT (Heap-Only Tuple) updates, which avoid touching indexes, but only when the changed columns are not indexed and the page has free space. Leaving some "fillfactor" headroom on heavily updated tables helps PostgreSQL use this fast path.',
        ],
    ],
    Metric::PostgresDeadTupleRatio->value => [
        'definitions' => [
            'This is the percentage of "dead" rows in your tables, that is, obsolete row versions left behind by UPDATE and DELETE operations and not yet reclaimed.',
            'It is computed from the "n_dead_tup" and "n_live_tup" counters: dead / (dead + live).',
        ],
        'why_it_matters' => [
            'PostgreSQL never overwrites a row in place: every UPDATE or DELETE leaves the old version on disk until autovacuum cleans it up. When dead rows pile up faster than autovacuum reclaims them, tables and indexes grow ("bloat"), queries scan more pages, and performance slowly degrades even though the amount of useful data has not changed.',
        ],
        'how_to_read' => [
            'A low ratio is healthy: it means autovacuum is keeping up with the write churn. A high or steadily rising ratio means dead rows are accumulating faster than they are reclaimed, which is a classic sign of bloat or of autovacuum falling behind.',
        ],
        'actions' => [
            [
                'case' => 'If the ratio is high on a specific table',
                'actions' => [
                    'Run a manual <code>VACUUM</code> (or <code>VACUUM ANALYZE</code>) to reclaim space immediately, then make autovacuum more aggressive on that table (lower "autovacuum_vacuum_scale_factor") so it does not fall behind again.',
                ],
            ],
            [
                'case' => 'If the ratio keeps climbing across the database',
                'actions' => [
                    'Autovacuum is likely under-resourced for your write volume. Increase "autovacuum_max_workers" and "autovacuum_vacuum_cost_limit", and check for long-running or idle-in-transaction sessions that hold back cleanup (see the Oldest transaction and Idle in transaction metrics).',
                ],
            ],
        ],
        'good_to_know' => [
            'Regular <code>VACUUM</code> reclaims dead space for reuse but does not shrink the file on disk; only <code>VACUUM FULL</code> (which takes an exclusive lock) actually returns space to the operating system. A long-running transaction anywhere in the database can block cleanup everywhere, because PostgreSQL must keep dead rows that the old transaction might still need to see.',
        ],
    ],
    Metric::PostgresIndexUsageRatio->value => [
        'definitions' => [
            'This is the percentage of row fetches that went through an index rather than a sequential (full table) scan.',
            'It is computed from the "idx_scan" and "seq_scan" related counters: index fetches / (index fetches + sequential fetches).',
        ],
        'why_it_matters' => [
            'Indexes let PostgreSQL jump straight to the rows it needs instead of reading an entire table. A high index usage ratio means most queries take the fast path; a low ratio means PostgreSQL is repeatedly scanning whole tables, which gets slower and slower as the data grows.',
        ],
        'how_to_read' => [
            'On a typical OLTP workload this ratio should be high, often above 95%. A low value means many queries fall back to sequential scans, usually because of missing indexes or queries written in a way that cannot use the existing ones.',
        ],
        'actions' => [
            [
                'case' => 'If the ratio is low',
                'actions' => [
                    'Identify the heaviest sequential scans (the Scans per second metric and "pg_stat_user_tables" help) and add indexes on the columns used in WHERE, JOIN, and ORDER BY clauses. Run <code>EXPLAIN ANALYZE</code> on slow queries to confirm the planner switches to an index.',
                ],
            ],
            [
                'case' => 'If a previously high ratio drops',
                'actions' => [
                    'A new query pattern, a code change, or stale planner statistics may be the cause. Run <code>ANALYZE</code> to refresh statistics, and check whether a recently added query is scanning a large table without a suitable index.',
                ],
            ],
        ],
        'good_to_know' => [
            'A sequential scan is not always bad: on small tables, or when a query genuinely needs most of the rows, PostgreSQL deliberately chooses a full scan because it is faster than using an index. A low ratio is only a problem when large, frequently queried tables are being scanned in full.',
        ],
    ],
    Metric::PostgresScansPerSec->value => [
        'definitions' => [
            'This chart shows the rate of table scans per second, split into Sequential scans and Index scans.',
            'It is derived from the "seq_scan" and "idx_scan" counters in "pg_stat_user_tables".',
        ],
        'why_it_matters' => [
            'It reveals <em>how</em> PostgreSQL is reaching your data over time. A sequential scan reads an entire table; an index scan jumps straight to the relevant rows. Watching the balance between the two shows whether your queries are taking the fast, indexed path or repeatedly reading whole tables.',
        ],
        'how_to_read' => [
            '<strong>Index scans</strong>: queries using an index. On a healthy OLTP workload this line should dominate.',
            '<strong>Sequential scans</strong>: full table reads. A few are normal (small tables, analytics), but a high or rising rate on large tables is a warning sign.',
        ],
        'actions' => [
            [
                'case' => 'If sequential scans are high or growing',
                'actions' => [
                    'Find which tables are being scanned and add indexes on the columns used in WHERE, JOIN, and ORDER BY. Confirm with <code>EXPLAIN ANALYZE</code> that the planner switches to an index scan afterwards.',
                ],
            ],
            [
                'case' => 'If both lines spike together',
                'actions' => [
                    'Look for a surge in query volume or a batch/report job. Correlate with the Transactions per second and Slow queries metrics to see whether the extra scans come from legitimate traffic or from inefficient queries.',
                ],
            ],
        ],
        'good_to_know' => [
            'Sequential scans are not inherently bad: on small tables PostgreSQL deliberately prefers them because a full scan is cheaper than an index lookup. The concern is sustained sequential scanning of <em>large</em> tables. This metric pairs naturally with the Index usage ratio, which summarizes the same information as a single percentage.',
        ],
    ],
    Metric::PostgresDeadlocksPerSec->value => [
        'definitions' => [
            'This chart shows the rate of deadlocks detected by PostgreSQL per second.',
            'It is the per-second derivative of the "deadlocks" counter in "pg_stat_database".',
        ],
        'why_it_matters' => [
            'A deadlock happens when two (or more) transactions each hold a lock the other needs, so neither can move forward. PostgreSQL detects this, picks one transaction as the victim, and aborts it with an error. Every deadlock is therefore a failed transaction the application must retry, and a sign of contention in how your code acquires locks.',
        ],
        'how_to_read' => [
            'This line should normally sit at zero. Any non-zero value means real transactions are being aborted. Occasional, isolated deadlocks under heavy concurrency can be tolerable, but a steady or rising rate points to a recurring locking pattern that needs fixing.',
        ],
        'actions' => [
            [
                'case' => 'If deadlocks happen regularly',
                'actions' => [
                    'Check the PostgreSQL logs: each deadlock is logged with the queries and locks involved. The most common fix is to make all transactions acquire locks (and update rows) in a consistent order, so they cannot form a cycle.',
                ],
            ],
            [
                'case' => 'If deadlocks come in bursts',
                'actions' => [
                    'Look for long transactions holding locks while doing extra work. Keep transactions short, touch the fewest rows possible, and make sure the application retries the aborted transaction instead of surfacing the error to the user.',
                ],
            ],
        ],
        'good_to_know' => [
            'A deadlock is different from simply waiting on a lock: a blocked transaction waits and eventually proceeds, while a deadlock can never resolve itself, so PostgreSQL must abort one party. The check runs after "deadlock_timeout" (1 second by default), which is also why deadlocks add latency even when they are rare. Use the Blocked sessions and Blocking chain metrics to see lock contention before it turns into a deadlock.',
        ],
    ],
    Metric::PostgresBlockedSessions->value => [
        'definitions' => [
            'This is the number of sessions currently waiting on a lock held by another session.',
            'It counts the backends that are stuck in a "waiting" state because another transaction has not yet released the lock they need.',
        ],
        'why_it_matters' => [
            'A blocked session is a query that has stopped making progress: it is alive but frozen, waiting for someone else to commit or roll back. A few brief waits are normal under concurrency, but sustained blocking means users are experiencing slow or hanging requests, and a single stuck transaction can pile up many waiters behind it.',
        ],
        'how_to_read' => [
            'This value should normally be zero or close to it. Short spikes during heavy write bursts are expected. A value that stays high, or keeps growing, means transactions are holding locks too long and a queue of waiters is forming.',
        ],
        'actions' => [
            [
                'case' => 'If blocked sessions stay high',
                'actions' => [
                    'Find the blocking transaction (the Blocking chain metric shows who blocks whom). It is often a long-running or idle-in-transaction session; ending or committing it releases the whole queue at once.',
                ],
            ],
            [
                'case' => 'If blocking happens repeatedly',
                'actions' => [
                    'Keep transactions short and commit promptly, acquire locks in a consistent order, and avoid doing slow work (external calls, user think-time) while a transaction is open. Watch the Idle in transaction metric, a frequent root cause.',
                ],
            ],
        ],
        'good_to_know' => [
            'Blocking is not the same as a deadlock: a blocked session will resume on its own once the lock is released, whereas a deadlock can never resolve and forces PostgreSQL to abort a transaction. Persistent blocking, however, is often the early stage that precedes deadlocks under load.',
        ],
    ],
    Metric::PostgresOldestTransaction->value => [
        'definitions' => [
            'This is the age (duration) of the longest-running transaction currently open on the database.',
            'It is measured as the elapsed time since that transaction started ("now() - xact_start" in "pg_stat_activity").',
        ],
        'why_it_matters' => [
            'A single old transaction holds back the whole database. As long as it stays open, PostgreSQL must keep every dead row that the transaction might still need to see, which blocks autovacuum from reclaiming space and causes table and index bloat across the entire instance, not just the tables that transaction touched.',
        ],
        'how_to_read' => [
            'On a healthy OLTP system, transactions last milliseconds to seconds, so this value should stay low. A transaction age measured in minutes or hours is a red flag: something is holding a transaction open far longer than it should.',
        ],
        'actions' => [
            [
                'case' => 'If the oldest transaction keeps growing',
                'actions' => [
                    'Identify it in "pg_stat_activity" (look at "state", "query", and "xact_start"). If it is stuck or abandoned, you can terminate it with <code>pg_terminate_backend(pid)</code> to release the space it is pinning.',
                ],
            ],
            [
                'case' => 'If long transactions recur',
                'actions' => [
                    'Look for application patterns that keep a transaction open during slow work: external API calls, user think-time, large batch jobs, or forgotten <code>BEGIN</code> without a matching commit. Split long units of work and commit more often.',
                ],
            ],
        ],
        'good_to_know' => [
            'This often goes hand in hand with the Idle in transaction metric: a transaction that is open but doing nothing is one of the most common causes of a high oldest-transaction age. Long-running read transactions matter too, because they also prevent vacuum from cleaning up dead rows.',
        ],
    ],
    Metric::PostgresIdleInTransaction->value => [
        'definitions' => [
            'This is the number of sessions in the "idle in transaction" state: they have opened a transaction (<code>BEGIN</code>) but are currently running no query, waiting on the application instead.',
            'It counts backends whose state is "idle in transaction" in "pg_stat_activity".',
        ],
        'why_it_matters' => [
            'An idle-in-transaction session is the worst of both worlds: it does no useful work, yet it still holds its locks and its transaction snapshot open. That pins dead rows (blocking autovacuum and causing bloat) and can block other sessions waiting on the same rows, all while nothing is actually happening on the database side.',
        ],
        'how_to_read' => [
            'This value should normally be zero or very low. A few brief occurrences are fine, but sessions that stay idle in transaction for seconds or minutes, or a count that keeps climbing, indicate an application that opens transactions and then leaves them hanging.',
        ],
        'actions' => [
            [
                'case' => 'If sessions stay idle in transaction',
                'actions' => [
                    'Find them in "pg_stat_activity" and fix the application flow so it commits or rolls back promptly. Avoid keeping a transaction open across external API calls, user interaction, or long computations.',
                ],
            ],
            [
                'case' => 'As a safety net',
                'actions' => [
                    'Set "idle_in_transaction_session_timeout" so PostgreSQL automatically aborts transactions left idle beyond a threshold. This protects the database from a single misbehaving client without waiting for a manual intervention.',
                ],
            ],
        ],
        'good_to_know' => [
            'This is different from a plain "idle" connection, which sits outside any transaction and is harmless. The danger comes specifically from holding a transaction open. Idle-in-transaction sessions are one of the most common root causes behind a high Oldest transaction age and rising Blocked sessions.',
        ],
    ],
    Metric::PostgresDatabaseSize->value => [
        'definitions' => [
            'This chart shows the total on-disk size of your database over time.',
            'It is measured with "pg_database_size()", which includes tables, indexes, and the space PostgreSQL keeps allocated.',
        ],
        'why_it_matters' => [
            'Tracking size over time tells you how fast your data is growing, helps with capacity planning, and surfaces problems early. A sudden jump or a steady climb that outpaces your real data growth often signals bloat (dead rows not reclaimed) rather than genuinely new data.',
        ],
        'how_to_read' => [
            'A smooth, gradual increase that matches your business growth is normal and expected. Watch instead for sharp steps, an accelerating slope, or growth that continues while the actual row count stays flat, which point to bloat, runaway logs, or unbounded tables.',
        ],
        'actions' => [
            [
                'case' => 'If size grows faster than your data',
                'actions' => [
                    'Suspect bloat: check the Dead tuple ratio and make sure autovacuum is keeping up. A <code>VACUUM</code> reclaims space for reuse; <code>VACUUM FULL</code> (with an exclusive lock) actually shrinks the files on disk.',
                ],
            ],
            [
                'case' => 'If you see a sudden jump',
                'actions' => [
                    'Look for a bulk import, a new large table, unrotated logs, or an index build. Use the Storage breakdown to see which tables and indexes consume the most space.',
                ],
            ],
        ],
        'good_to_know' => [
            'This size reflects space allocated to PostgreSQL, not just live data: deleting rows usually does not shrink the file, because the freed space is kept for future reuse. That is why database size can stay high even after a large delete until a <code>VACUUM FULL</code> or table rewrite is performed.',
        ],
    ],
    Metric::MysqlVersion->value => [
        'definitions' => [
            'MySQL is one of the most widely used open-source relational database management systems.',
            'Endoflife timeline: <a href="https://endoflife.date/mysql" target="_blank">https://endoflife.date/mysql</a>.',
        ],
        'why_it_matters' => [
            'Databases are the most sensitive part of your stack. Running an EOL (End of Life) version means no more security patches, making your data vulnerable to exploits. Newer versions also introduce major performance boosts like improved indexing and JSON processing.',
        ],
        'good_to_know' => [
            'MySQL has shifted towards a new release model including "Innovation" releases (fast-paced) and "LTS" releases (stable/long-term). For production environments, always favor the LTS versions to ensure 5 to 8 years of peace of mind.',
        ],
    ],
    Metric::MysqlConnectionsUsage->value => [
        'definitions' => [
            'This represents the percentage of the connection pool currently occupied by active clients.',
            'It compares your current open connections to the "max_connections" limit defined in your MySQL configuration.',
        ],
        'why_it_matters' => [
            'Each connection consumes memory on the database server. If you reach 100%, MySQL will reject any new requests from your PHP application, leading to a total site outage. Monitoring this helps you scale your capacity before the crash happens.',
        ],
        'how_to_read' => [
            'A healthy usage stays below 70%. If you see frequent spikes above 80%, you are in the "danger zone" where a small traffic surge could take your database offline.',
        ],
        'actions' => [
            [
                'case' => 'If usage is consistently high (> 80%)',
                'actions' => [
                    'First, check if your application is properly closing connections or using a connection pool. If the traffic is legitimate, increase the "max_connections" value in your MySQL settings (my.cnf), provided your server has enough RAM.',
                ],
            ],
            [
                'case' => 'If usage spikes suddenly to 100%',
                'actions' => [
                    'Check for "slow queries" that keep connections open longer than necessary. A single unoptimized query can cause a bottleneck where connections pile up while waiting for a response.',
                ],
            ],
        ],
        'good_to_know' => [
            'Beware: simply increasing "max_connections" isn\'t always the solution. Each connection uses RAM; if you allow 1000 connections on a small server, you might trade a "Too many connections" error for a complete server crash due to memory exhaustion (OOM).',
        ],
    ],
    Metric::MysqlMaxConnectionsReached->value => [
        'definitions' => [
            'This metric records the historical "peak" of simultaneous connections since the MySQL server last started.',
            'It acts as a high-water mark, showing you the absolute maximum stress your database has had to handle.',
        ],
        'why_it_matters' => [
            'Even if your server looks calm right now, this value reveals whether you came close to the breaking point in the past. It is the perfect indicator for capacity planning: it tells you whether your current configuration is sufficient to absorb your usual traffic peaks.',
        ],
        'how_to_read' => [
            'Look at the gap between this historical maximum and your allowed limit. If the bar has already reached or come close to the maximum (95% or more), your infrastructure is undersized for your peak events.',
        ],
        'actions' => [
            [
                'case' => 'If the historical maximum is very close to the limit',
                'actions' => [
                    'You have been lucky so far, but a slightly larger peak will crash your app. Increase "max_connections" now, or implement a connection proxy (like ProxySQL) to better manage how your app talks to the database.',
                ],
            ],
            [
                'case' => 'If the current usage is low but the historical peak is high',
                'actions' => [
                    'Analyze your logs around the time of that peak. Was it a planned task (like a backup or a heavy report)? If so, consider scheduling those tasks during off-peak hours to lower the pressure.',
                ],
            ],
        ],
        'good_to_know' => [
            'Keep in mind that this counter is reset whenever MySQL restarts. If you just rebooted your server, this "peak" will be misleadingly low until your next traffic surge.',
        ],
    ],
    Metric::MysqlQueriesPerSecond->value => [
        'definitions' => [
            'This represents the total number of SQL queries (SELECT, INSERT, UPDATE, DELETE, etc.) processed by MySQL every second.',
            'It is the "speedometer" of your database activity.',
        ],
        'why_it_matters' => [
            'A database is often the first bottleneck of an application. Monitoring QPS helps you identify "query floods" (where a single page load triggers hundreds of unoptimized queries) and allows you to measure the direct impact of adding a caching layer like Redis.',
        ],
        'how_to_read' => [
            'Compare this with your "Request Per Second" (RPS). If your traffic is stable but your QPS spikes, your application is likely doing more database work than necessary for the same number of visitors.',
        ],
        'actions' => [
            [
                'case' => 'If QPS spikes while visitor traffic (RPS) remains flat',
                'actions' => [
                    'You likely have an "N+1 query" problem or a cache miss issue. Review your recent code changes for loops that execute database queries inside them.',
                ],
            ],
            [
                'case' => 'If QPS is high and database CPU is also high',
                'actions' => [
                    'Enable the "Slow Query Log" to identify the most expensive queries. Adding missing indexes or optimizing your JOINs can drastically reduce the CPU cost of each query, even if the QPS remains high.',
                ],
            ],
            [
                'case' => 'If QPS drops to zero',
                'actions' => [
                    'Your application is no longer reaching the database. Check for network issues, a crashed PHP process, or a global application error preventing queries from being sent.',
                ],
            ],
        ],
        'good_to_know' => [
            'The best query is the one that never happens. If your QPS is too high, the most effective solution is often to implement application-side caching (Redis/Memcached) for frequently accessed, slow-changing data.',
        ],
    ],
    Metric::MysqlSlowQueriesTop->value => [
        'definitions' => [
            'This table identifies the "most expensive" queries currently running on your server, ranked by their execution time and how often they occur.',
            'It extracts data directly from the MySQL Performance Schema, providing a live diagnostic of database bottlenecks.',
        ],
        'why_it_matters' => [
            'A single unoptimized query can be the "bottleneck" that slows down your entire application. By fixing just the top 2 or 3 queries in this list, you can often reduce your overall database load by 50% or more, improving the speed for all your users.',
        ],
        'how_to_read' => [
            'Focus on queries with a high "Total Time". A query that takes 1 second but runs 1,000 times a minute is often more damaging than a query that takes 10 seconds but only runs once a day.',
        ],
        'actions' => [
            [
                'case' => 'If a query appears at the top of the list',
                'actions' => [
                    'Run an "EXPLAIN" on the query to see if it uses indexes. If not, adding a single index on a WHERE or JOIN column is often the fastest fix.',
                    'Check if the query is returning more data than necessary (avoid "SELECT *").',
                    'Determine if the results can be cached in Redis to avoid hitting the database every time.',
                ],
            ],
        ],
        'good_to_know' => [
            'The Performance Schema is highly efficient, but it can be reset. If the table seems empty, it might be because the server just restarted or the schema collection was recently cleared. Use this card as a real-time "radar" for performance leaks.',
        ],
    ],
    Metric::MysqlInnodbBufferPoolUsage->value => [
        'definitions' => [
            'This represents the occupancy rate of the InnoDB Buffer Pool, the primary memory area where MySQL caches your data and indexes.',
            'It indicates how much of your allocated database RAM is currently "warm" with data.',
        ],
        'why_it_matters' => [
            'A database performs at its best when it doesn\'t have to touch the disk. A high usage is actually desirable: it means MySQL is using the available RAM to protect your application from slow disk I/O. If your "hot" data doesn\'t fit here, your performance will collapse as the server starts swapping data to the disk constantly.',
        ],
        'how_to_read' => [
            '<strong>High usage (90-100%) is the target state</strong> for a healthy production server, indicating that your most frequent data is successfully cached in memory.',
            'Low usage usually means the server was recently restarted or the database is smaller than the allocated memory.',
        ],
        'actions' => [
            [
                'case' => 'If usage is 100% and you notice slow response times',
                'actions' => [
                    'Check your "Buffer Pool Hit Rate". If it is below 95%, it means MySQL is forced to go to the disk too often. If you have available RAM on your server, increase "innodb_buffer_pool_size" in your configuration.',
                ],
            ],
            [
                'case' => 'If usage is low (< 50%) after several hours of uptime',
                'actions' => [
                    'Either your database is very small, or you have allocated way too much RAM to MySQL. You could potentially reduce the buffer pool size to free up memory for other services like PHP workers or Redis.',
                ],
            ],
        ],
        'good_to_know' => [
            'On a dedicated database server, it is common to allocate up to 70-80% of total system RAM to the InnoDB Buffer Pool. However, on a shared server (PHP + MySQL), be careful not to starve your PHP processes by being too generous with this setting.',
        ],
    ],
    Metric::MysqlInnodbBufferPoolHitRate->value => [
        'definitions' => [
            'This metric measures the efficiency of your cache: the percentage of data requests that MySQL found instantly in RAM (Hit) versus those it had to fetch from the slow disk (Miss).',
            'In the database world, a "Hit" takes microseconds, while a "Miss" can take milliseconds—a 1,000x difference in speed.',
        ],
        'why_it_matters' => [
            'A high Hit Rate is what makes your application feel "snappy." If this value drops, your disk usage (I/O) will skyrocket, your CPU will wait for the data to arrive, and your users will experience frustrating slowness, even if your traffic hasn\'t increased.',
        ],
        'how_to_read' => [
            '<b>Aim for > 98% in production.</b> A drop below 95% is a warning; below 90%, your database is effectively "suffocating" because it spends more time waiting for the disk than processing your data.',
        ],
        'actions' => [
            [
                'case' => 'If the Hit Rate is consistently low (< 95%)',
                'actions' => [
                    'Your "Working Set" (the data your app needs daily) is larger than your RAM. You must either increase "innodb_buffer_pool_size" or optimize your queries to read fewer rows.',
                ],
            ],
            [
                'case' => 'If the Hit Rate drops suddenly during specific hours',
                'actions' => [
                    'You likely have a "Cache Thashing" event. A heavy background task (like a full database export or a massive report without indexes) is "flushing" your good data out of the RAM to make room for data you only need once.',
                ],
            ],
        ],
        'good_to_know' => [
            'A low Hit Rate on a newly started server is normal: the cache is "Cold." It takes time (from minutes to hours) for the Hit Rate to climb as MySQL "learns" which data is most important to keep in RAM.',
        ],
    ],
    Metric::MysqlInnodbRamRatio->value => [
        'definitions' => [
            'This indicates the proportion of the total server RAM strictly reserved for the MySQL InnoDB Buffer Pool.',
            'It represents the "slice of the cake" dedicated to database caching versus the rest of the system (OS, PHP, Redis).',
        ],
        'why_it_matters' => [
            'Proper memory allocation is a balancing act. If this ratio is too low, your database will be slow because it can\'t cache enough data. If it is too high, your server might crash because there is no RAM left for your PHP workers or the Operating System itself (OOM risk).',
        ],
        'how_to_read' => [
            'On a <b>dedicated database server</b>, aim for 70-80%. On a <b>shared server</b> (running PHP + Caddy + MySQL), a ratio between 40% and 50% is usually safer to let other processes breathe.',
        ],
        'actions' => [
            [
                'case' => 'If the ratio is very high (> 85%) on a shared server',
                'actions' => [
                    'You are at high risk of an "Out of Memory" crash. If your PHP workers suddenly need more RAM, the OS will have to kill processes. Consider reducing "innodb_buffer_pool_size" to leave at least 1-2 GB of free RAM for the system.',
                ],
            ],
            [
                'case' => 'If the ratio is low (< 20%) but your "Hit Rate" is also low',
                'actions' => [
                    'You are under-utilizing your hardware. You have plenty of available RAM that could be used to speed up your database. Increase the buffer pool size to improve performance.',
                ],
            ],
        ],
        'good_to_know' => [
            'The InnoDB Buffer Pool is "locked" memory. Even if your database is empty, MySQL will reserve this entire amount of RAM upon startup. Always calculate your "available RAM" by subtracting this ratio first.',
        ],
    ],
    Metric::MysqlInnodbReadsPerSec->value => [
        'definitions' => [
            'This represents the number of physical read operations the InnoDB engine performs against the disk every second.',
            'It is the direct measure of "Cache Misses" at the storage engine level.',
        ],
        'why_it_matters' => [
            'A high number of disk reads is a major performance bottleneck. Disk I/O is thousands of times slower than RAM. If this number is high, your CPU is likely spending most of its time waiting for the disk to respond (I/O Wait), leading to slow query response times and a sluggish application.',
        ],
        'how_to_read' => [
            '<b>Lower is better.</b> In a well-tuned system, this should ideally be close to zero for your most frequent queries. A high value combined with low "Buffer Pool Usage" suggests your most important data isn\'t staying in memory.',
        ],
        'actions' => [
            [
                'case' => 'If reads per second are consistently high',
                'actions' => [
                    'Increase the <code>innodb_buffer_pool_size</code>. This is the single most important setting in MySQL; it should generally be set to 70-80% of your total server RAM if the server is dedicated to the database.',
                    'Check for "Missing Indexes." Queries that perform full table scans force MySQL to read the entire dataset from disk into memory, causing a spike in reads.',
                ],
            ],
            [
                'case' => 'If you see a sudden spike in reads per second',
                'actions' => [
                    'Identify "Cold Data" access. This happens when a user requests very old information (like a 5-year-old invoice) that isn\'t in the cache, or during a large database backup/export.',
                    'Review your slow query log. Look for queries that examine a large number of rows without using an index.',
                ],
            ],
            [
                'case' => 'If reads are low but queries are still slow',
                'actions' => [
                    'The bottleneck may be in "Writes" rather than "Reads," or you may have a "Locking" issue where queries are waiting for each other rather than for the disk.',
                ],
            ],
        ],
        'good_to_know' => [
            'After a database restart, this metric will naturally be high for a short period. This is called "Warming up the cache," as MySQL must read data from the disk to fill the empty Buffer Pool. Performance will stabilize once the "Hot" data is back in RAM.',
        ],
    ],
    Metric::MysqlInnodbWritesPerSec->value => [
        'definitions' => [
            'This measures the frequency of physical write operations (I/O) performed by the InnoDB engine on your storage every second.',
            'It represents the moment where data modified in memory is "flushed" and safely persisted to the disk.',
        ],
        'why_it_matters' => [
            'Disk writing is the slowest operation a database performs. If this value is too high, your disk "I/O Wait" will increase, slowing down the entire server. Constant high write activity can also prematurely wear out SSD storage in intensive environments.',
        ],
        'how_to_read' => [
            'A spike here usually follows a wave of <code>INSERT</code>, <code>UPDATE</code>, or <code>DELETE</code> queries. If you see high writes without a corresponding increase in user activity, a background task (like a log rotation or a massive database migration) is likely running.',
        ],
        'actions' => [
            [
                'case' => 'If Writes Per Second spike to the disk\'s physical limits',
                'actions' => [
                    'Identify "Write-Heavy" processes. Use batch inserts (inserting 1000 rows in one query) instead of multiple individual queries to reduce the number of disk flushes.',
                    'Check your "innodb_flush_log_at_trx_commit" setting. If set to 1 (default), every single transaction forces a disk write. Setting it to 2 can drastically reduce disk pressure at the cost of a small risk of data loss in case of a power failure.',
                ],
            ],
            [
                'case' => 'If writes are high but database activity (QPS) is low',
                'actions' => [
                    'Your "Redo Log" or "Buffer Pool" might be misconfigured, forcing MySQL to write data to disk too frequently. Review your InnoDB log file size settings.',
                ],
            ],
        ],
        'good_to_know' => [
            'MySQL doesn\'t write to disk the exact millisecond you execute a query; it groups them in memory first. This is why you might see a "delayed" spike in writes after a burst of activity.',
        ],
    ],
    Metric::MysqlTablesLockWaitsPerSec->value => [
        'definitions' => [
            'This counts how many times per second a query was forced to pause because another process was locking the table it needed.',
            'It represents "Traffic Jams" at the database level: multiple queries fighting for the same resource simultaneously.',
        ],
        'why_it_matters' => [
            'Lock contention is a silent performance killer. Even with the fastest CPU and SSD, your application will feel slow if queries are stuck waiting for each other. High lock waits often lead to "deadlocks" or time-out errors in your PHP logs.',
        ],
        'how_to_read' => [
            'Ideally, this should be near zero. If you see persistent values here, it means your database design or your transaction logic is causing bottlenecks that prevent parallel processing.',
        ],
        'actions' => [
            [
                'case' => 'If Lock Waits spike suddenly',
                'actions' => [
                    'Use "SHOW FULL PROCESSLIST" to identify long-running queries that are blocking others. Often, a slow UPDATE or a large ALTER TABLE is the culprit.',
                    'Check for "uncommitted transactions": a developer might have started a transaction in a script but never called "commit", leaving the table locked indefinitely.',
                ],
            ],
            [
                'case' => 'If Lock Waits are consistently high on InnoDB tables',
                'actions' => [
                    'Ensure you are using the correct indexes. Without an index, MySQL might lock the entire table instead of just the specific row it needs to update.',
                    'Review your transaction isolation levels. Sometimes, switching from "Repeatable Read" to "Read Committed" can reduce locking pressure, but this requires a careful review of your data integrity needs.',
                ],
            ],
        ],
        'good_to_know' => [
            'Modern InnoDB engines use "Row-Level Locking" (locking only the line being changed). If you see high "Table-Level" locks, it is often a sign of very old MyISAM tables being used or a query so broad that MySQL has no choice but to lock everything to ensure safety.',
        ],
    ],
    Metric::MysqlTablesLockWaitsPercent->value => [
        'definitions' => [
            'This represents the ratio of queries that were delayed by a lock compared to the total number of queries processed.',
            'It measures the "Friction Rate" of your database: how often your application is competing against itself for data access.',
        ],
        'why_it_matters' => [
            'A high percentage is more alarming than a high raw number. If 20% of your queries are waiting for locks, your users will perceive the site as slow or unstable, regardless of your server\'s power. It indicates a fundamental concurrency bottleneck in your application logic.',
        ],
        'how_to_read' => [
            '<b>Target: < 1%.</b> Anything above 5% is a sign of serious contention. If this value climbs, it means that adding more traffic will not just increase load, but will exponentially slow down the system due to queueing effects.',
        ],
        'actions' => [
            [
                'case' => 'If the percentage is high while CPU usage is low',
                'actions' => [
                    'Your database is "idle-waiting." This is almost always caused by inefficient transactions. Break down large transactions into smaller ones and ensure that your PHP code doesn\'t perform slow external tasks (like sending an email) while a database transaction is still open.',
                ],
            ],
            [
                'case' => 'If the percentage spikes during write-heavy operations',
                'actions' => [
                    'Check for "Lock Escalation." If you are updating many rows without a proper index, MySQL might escalate a row lock to a full table lock, blocking everyone else. Optimize your "WHERE" clauses to be as specific as possible.',
                ],
            ],
        ],
        'good_to_know' => [
            'Locks are a natural part of database integrity, but they should be near-instant. A high percentage often reveals that the database is being used for things it shouldn\'t be, like using a table as a "job queue" without dedicated tools like Redis or RabbitMQ.',
        ],
    ],
    Metric::MysqlThreadsConnected->value => [
        'definitions' => [
            'This counts all currently open client connections, including those actively running a query and those staying idle (waiting for the next command).',
            'It represents the real-time "occupancy" of your database connection pool.',
        ],
        'why_it_matters' => [
            'Each connection has a memory overhead. If you have thousands of idle connections, you are wasting RAM that could be used for the Buffer Pool (caching). More importantly, if this reaches your "max_connections" limit, no new users will be able to access your site.',
        ],
        'how_to_read' => [
            'Monitor the gap between "Threads Connected" and "Threads Running". If you have 100 connected but only 2 running, your application is keeping connections open for too long without using them (check your "wait_timeout" setting).',
        ],
        'actions' => [
            [
                'case' => 'If the number of connected threads is consistently near your limit',
                'actions' => [
                    'Review your application\'s connection lifecycle. Ensure you are not using persistent connections (pconnect) unnecessarily and that your scripts close connections as soon as their database work is finished.',
                ],
            ],
            [
                'case' => 'If you see a "spike" of connections that never close',
                'actions' => [
                    'You might have a "leaky" application or a slow external API call. If PHP is waiting for an external service while holding a MySQL connection open, the threads will pile up until the server crashes.',
                ],
            ],
        ],
        'good_to_know' => [
            'Idle connections (in "Sleep" state) are often harmless in small numbers, but they can hide a deeper problem: if your web server has a high timeout, these sleepers can eventually hit your connection limit during a traffic peak.',
        ],
    ],
    Metric::MysqlThreadsRunning->value => [
        'definitions' => [
            'This represents the number of connections that are currently and actively processing a query.',
            'Unlike "Threads Connected", this ignores idle connections and focuses only on the actual workload being executed.',
        ],
        'why_it_matters' => [
            'This is your primary indicator of database saturation. Ideally, this number should be low. If "Threads Running" stays high, your CPU is likely struggling to keep up, leading to a massive increase in latency for every single user on your site.',
        ],
        'how_to_read' => [
            'Compare this to your number of CPU cores. If "Threads Running" is consistently higher than your available cores, your database is multitasking beyond its physical capacity, and queries are waiting for their turn to be processed by the processor.',
        ],
        'actions' => [
            [
                'case' => 'If Threads Running spikes while traffic is normal',
                'actions' => [
                    'You have "Sticky Queries." One or more complex queries are taking too long to finish, causing new incoming queries to pile up. Use "SHOW PROCESSLIST" to kill the blocking query and investigate its missing indexes.',
                ],
            ],
            [
                'case' => 'If Threads Running is high and CPU usage is at 100%',
                'actions' => [
                    'Your server is under-provisioned for the complexity of your queries. Optimize your most frequent SQL statements or upgrade your server to a model with more CPU cores.',
                ],
            ],
        ],
        'good_to_know' => [
            'A very high number of Threads Running can lead to "Internal Thrashing," where the database spends more time context-switching between tasks than actually executing them. In this state, the server can become completely unresponsive even if the RAM is still free.',
        ],
    ],
    Metric::MysqlThreadCacheMiss->value => [
        'definitions' => [
            'This represents the percentage of new connections that forced MySQL to create a fresh system thread instead of reusing an idle one from the cache.',
            'It measures the efficiency of the "recycling system" for database connections.',
        ],
        'why_it_matters' => [
            'Creating a thread is expensive. If your "Miss Rate" is high, your server wastes CPU cycles constantly opening and closing threads. On a busy site, this overhead can become a significant performance bottleneck, especially during traffic spikes (connection churn).',
        ],
        'how_to_read' => [
            '<b>Target: < 1% for stable applications.</b> A high value indicates that your cache is too small to handle the rate at which new connections are arriving and leaving.',
        ],
        'actions' => [
            [
                'case' => 'If the Miss Rate is consistently high (> 5%)',
                'actions' => [
                    'Increase the "thread_cache_size" in your MySQL configuration. A good rule of thumb is to set it to at least the value of your "Max Peak Connections" (see MysqlMaxConnectionsReached).',
                ],
            ],
            [
                'case' => 'If you see spikes in the Miss Rate despite a large cache',
                'actions' => [
                    'Your application might be opening and closing connections too aggressively. Check if your scripts are connecting to the database multiple times per request instead of reusing a single connection.',
                ],
            ],
        ],
        'good_to_know' => [
            'If you use a high number of PHP Workers (FrankenPHP) or a connection pooler, your Thread Cache Miss rate should naturally be near zero, as connections are kept alive and reused constantly.',
        ],
    ],
    Metric::MysqlSlowQueriesCount->value => [
        'definitions' => [
            'This metric tracks the total number of queries that exceeded the "long_query_time" threshold defined in your MySQL configuration.',
            'It provides both a real-time count and a historical chart to visualize when your database struggles most.',
        ],
        'why_it_matters' => [
            'A sudden spike in slow queries is often the "smoking gun" behind application timeouts or 504 errors. Monitoring the evolution of this count allows you to see if your database performance is degrading over time (e.g., as your tables grow) or if specific events like cron jobs are paralyzing your server.',
        ],
        'how_to_read' => [
            '<b>Ideally, this should be zero.</b> Any increase in the chart indicates that some requests are taking longer than acceptable (usually 1 or 2 seconds). Look for patterns: do slow queries happen every hour? Every Monday morning? This will lead you to the culprit.',
        ],
        'actions' => [
            [
                'case' => 'If the slow query count increases after a deployment',
                'actions' => [
                    'Check your new code for missing indexes on frequently used tables or complex JOINs that scan millions of rows.',
                ],
            ],
            [
                'case' => 'If slow queries appear at fixed intervals (e.g., every night)',
                'actions' => [
                    'You likely have a heavy background task (export, backup, or reporting) that is competing for resources. Optimize the task or move it to a lower-traffic period.',
                ],
            ],
        ],
        'good_to_know' => [
            'This metric depends entirely on your <code>long_query_time</code> setting. If it is set to 10s, you might miss many "moderately slow" queries (e.g., 2s) that still frustrate users. For a snappy application, a threshold of 1s or 2s is recommended.',
        ],
    ],
    Metric::MysqlTmpTables->value => [
        'definitions' => [
            'This represents the percentage of internal temporary tables that were forced to disk because they were too large to fit in memory.',
            'Temporary tables are used by MySQL for complex operations like GROUP BY, DISTINCT, or UNION.',
        ],
        'why_it_matters' => [
            'Memory is fast; disks are slow. When this percentage is high, it means your database is constantly writing and reading temporary files on your storage, causing a massive latency spike for the end user and increasing Disk I/O pressure.',
        ],
        'how_to_read' => [
            '<b>Aim for less than 10%.</b> A high or increasing value indicates that your queries are either unoptimized (processing too much data) or that your allocated memory for temporary work is too small.',
        ],
        'actions' => [
            [
                'case' => 'If the "On Disk" percentage is high (> 20%)',
                'actions' => [
                    'Identify queries using "ORDER BY" or "GROUP BY" on columns that are not indexed. These are the main culprits for large temporary tables.',
                    'Check if you are selecting unnecessary large columns (like BLOB or TEXT fields) in complex queries, as these often force a move to disk immediately.',
                    'If your server has enough RAM, consider increasing "tmp_table_size" and "max_heap_table_size" in your MySQL configuration.',
                ],
            ],
            [
                'case' => 'If the count of temporary tables (Memory + Disk) is very high',
                'actions' => [
                    'Review your application logic. You might be asking the database to perform complex sorting or grouping that could be handled more efficiently by your application code or via a better indexing strategy.',
                ],
            ],
        ],
        'good_to_know' => [
            'Beware: increasing "tmp_table_size" affects <em>each</em> connection. If you set it to 256MB and have 100 connections, you could theoretically use 25GB of RAM just for temporary tables, leading to an "Out of Memory" crash.',
        ],
    ],
    Metric::MysqlDataWeight->value => [
        'definitions' => [
            'This represents the cumulative disk space occupied by your MySQL databases, including both data tables and their associated indexes.',
            'It is the actual "physical weight" of your information on the storage device.',
        ],
        'why_it_matters' => [
            'Running out of disk space is a fatal error that will stop your database instantly and can lead to data corruption. Furthermore, a larger database requires more RAM for the Buffer Pool to remain efficient. If your data weight grows faster than your RAM, your performance will inevitably drop.',
        ],
        'how_to_read' => [
            'A linear growth is healthy for most businesses. However, pay attention to the ratio between data and indexes: if your indexes weigh more than your data, you might have redundant or unused indexes that are slowing down your "WRITE" operations for nothing.',
        ],
        'actions' => [
            [
                'case' => 'If you see a sudden, massive jump in size',
                'actions' => [
                    'Check for "log tables" or "audit trails" that might be out of control. Use a query to rank your tables by size (Data vs Index) to find the specific culprit.',
                    'Check if a recent large import or a database migration (like adding a column with a default value) has duplicated some storage space.',
                ],
            ],
            [
                'case' => 'If the database is approaching 80% of your total disk capacity',
                'actions' => [
                    'Implement a data retention policy (archiving old rows to S3 or a secondary DB).',
                    'Run "OPTIMIZE TABLE" on tables where you have deleted a lot of data to reclaim "fragmented" space (especially for InnoDB).',
                ],
            ],
        ],
        'good_to_know' => [
            'Indexes are not free. Every index you add to speed up "SELECT" queries increases your Data Weight and slows down "INSERT/UPDATE" queries. Keep your indexing strategy lean and mean.',
        ],
    ],
    Metric::PhpVersion->value => [
        'definitions' => [
            'PHP is the server-side scripting language used to run your application.',
            'Official timeline: <a href="https://www.php.net/supported-versions.php" target="_blank">https://www.php.net/supported-versions.php</a>.',
            'Endoflife timeline: <a href="https://endoflife.date/php" target="_blank">https://endoflife.date/php</a>.',
        ],
        'why_it_matters' => [
            'PHP is the heart of your app. Using an unsupported version (EOL) means no more security patches, making your server a target for exploits. Furthermore, each major version (e.g., from 7.4 to 8.x) typically brings a 10% to 30% performance boost and reduced memory footprint without changing a single line of code.',
        ],
        'good_to_know' => [
            'PHP versions follow a strict 3-year lifecycle: 2 years of active bug fixes and 1 final year of critical security fixes. Staying within this window is the best way to ensure both stability and security.',
        ],
    ],
    Metric::PhpSapi->value => [
        'definitions' => [
            'This indicates the "Server API" (SAPI) layer that PHP is currently using to communicate with your web server.',
            'It defines the execution model of your application: how processes are started, managed, and recycled.',
        ],
        'why_it_matters' => [
            'The SAPI is the biggest architectural choice for your stack. For example, switching from a legacy SAPI (like Apache Handler) to a modern one (like FrankenPHP or FPM) can drastically reduce your server\'s response time and resource consumption. It also dictates which configuration files (php.ini) are loaded.',
        ],
        'how_to_read' => [
            '<b>frankenphp</b>: You are using a modern, high-performance Go-based server. This SAPI is ideal for "Worker Mode" where the app stays in RAM.',
            '<b>fpm-fcgi</b>: The industry standard for many years. PHP runs as a separate pool of processes. Stable and scalable.',
            '<b>cli</b>: This means you are running a command-line script. This SAPI usually has no time limits and different memory constraints.',
            '<b>apache2handler / cgi-fcgi</b>: These are legacy models. If you see this in production, you are likely missing out on modern performance optimizations.',
        ],
        'good_to_know' => [
            'Each SAPI often has its own separate configuration. If you change a setting in your CLI (for a cron job) and it doesn\'t reflect on your website, it\'s because the Web SAPI (FPM/FrankenPHP) uses a different environment.',
        ],
    ],
    Metric::PhpOpcacheMemory->value => [
        'definitions' => [
            'This indicates the percentage of allocated RAM currently occupied by the OPcache bytecode of your PHP scripts.',
            'It represents the "storage space" available for pre-compiled code.',
        ],
        'why_it_matters' => [
            'If this memory is full, PHP can no longer cache new scripts. Even worse, it may start "evicting" old ones, forcing the server to re-compile your code on every request. This causes a massive and immediate spike in CPU usage and slows down your application significantly.',
        ],
        'how_to_read' => [
            '<b>Aim for 40% to 80% usage.</b> Unlike a database buffer pool, you want to keep a "safety margin" here. If you reach 100%, your performance is no longer guaranteed.',
        ],
        'actions' => [
            [
                'case' => 'If usage is consistently above 90%',
                'actions' => [
                    'Increase <code>opcache.memory_consumption</code> in your php.ini. Modern frameworks like Laravel or Symfony, especially with many vendor packages, often require at least 256MB or 512MB.',
                ],
            ],
            [
                'case' => 'If usage spikes after a deployment',
                'actions' => [
                    'This is normal, but ensure you are using <code>opcache_reset()</code> or a smooth reload (SIGUSR2) of your PHP processes to clear out old versions of the code and free up memory.',
                ],
            ],
        ],
        'good_to_know' => [
            'Memory is only half the story. You must also monitor "Cached Keys" (or Max Accelerated Files). Even if you have plenty of RAM, if you hit the file limit, OPcache will stop caching new files. Always check both.',
        ],
    ],
    Metric::PhpOpcacheHitRate->value => [
        'definitions' => [
            'This represents the percentage of requests where PHP found the pre-compiled script in RAM (Hit) versus having to read and compile it from the disk (Miss).',
            'It is the ultimate indicator of your PHP execution efficiency.',
        ],
        'why_it_matters' => [
            'Every "Miss" is a penalty for your CPU: it must open the file, parse the syntax, and generate bytecode. If your Hit Rate is low, your server spends more time "preparing" the code than actually executing it, leading to higher latency and server load.',
        ],
        'how_to_read' => [
            '<b>Target: > 99% in production.</b> On a stable application, this number should be near perfect. A rate falling below 95% indicates your cache is "thrashing"—constantly discarding code only to re-compile it seconds later.',
        ],
        'actions' => [
            [
                'case' => 'If the Hit Rate is consistently below 98%',
                'actions' => [
                    'Check if <code>opcache.max_accelerated_files</code> is high enough. If your project (including the <code>vendor/</code> folder) has more files than this limit, the hit rate will never reach 100%.',
                    'Disable <code>opcache.validate_timestamps</code> in production (set it to 0). This prevents PHP from wasting time checking the disk to see if your files have changed on every single request.',
                ],
            ],
            [
                'case' => 'If the Hit Rate drops and stays low after a deploy',
                'actions' => [
                    'You might be saturating the memory. Increase <code>opcache.memory_consumption</code>. If using deployment tools like Capistrano or Deployer, ensure that symlink changes are correctly handled by your PHP SAPI.',
                ],
            ],
        ],
        'good_to_know' => [
            'A low Hit Rate when the server first starts is normal (the cache is "cold"). For high-traffic apps, consider using <code>opcache.preload</code> (PHP 7.4+) to compile your entire framework at startup and achieve a 100% Hit Rate from the very first request.',
        ],
    ],
    Metric::PhpFpmActiveProcesses->value => [
        'definitions' => [
            'This represents the percentage of PHP-FPM worker processes currently engaged in executing a script versus the maximum number of workers allowed (pm.max_children).',
            'It measures the immediate concurrency saturation of your PHP environment.',
        ],
        'why_it_matters' => [
            'When this reaches 100%, your server is "full." New visitors will experience significant delays or "Server Busy" errors as their requests are placed in a wait queue. Persistent high usage often indicates that your scripts are taking too long to finish, or that you simply haven\'t allocated enough workers for your traffic level.',
        ],
        'how_to_read' => [
            '<b>A healthy range is 10% to 60% during peak hours.</b> This leaves enough "headroom" to handle sudden traffic spikes without slowing down the user experience.',
        ],
        'actions' => [
            [
                'case' => 'If Active Processes frequently hit 100%',
                'actions' => [
                    'Increase <code>pm.max_children</code> in your FPM pool configuration if your server has available RAM. Each worker typically consumes 30MB-80MB of memory.',
                    'Check for "External Latency": if your PHP script is waiting for a slow 3rd party API or a slow MySQL query, that worker remains "Active" and blocked, preventing others from using it.',
                ],
            ],
            [
                'case' => 'If Active Processes are high but CPU usage is low',
                'actions' => [
                    'This is a classic sign of I/O blocking. Your PHP workers aren\'t actually "working" (calculating); they are "waiting" for something else (database, disk, or network). Optimize your downstream dependencies to free up these workers faster.',
                ],
            ],
        ],
        'good_to_know' => [
            'There is a limit to how many workers you can create. Setting <code>pm.max_children</code> too high on a small server will lead to "Out of Memory" crashes. Always calculate your max workers based on available RAM: <code>(Total RAM - System RAM) / Average Process Size</code>.',
        ],
    ],
    Metric::PhpFpmIdleProcesses->value => [
        'definitions' => [
            'This counts the number of worker processes that are currently inactive but ready to handle a new request immediately.',
            'It represents your server\'s "instant capacity" to handle a sudden surge in traffic.',
        ],
        'why_it_matters' => [
            'Spawning a new PHP process is "expensive" in terms of time and CPU. If a user arrives and there are zero idle processes, the server must wait for a process to be created or for a busy one to finish. This creates a perceptible delay (latency) for your users, even if the server isn\'t technically overloaded.',
        ],
        'how_to_read' => [
            '<b>A consistent buffer of 5–10 idle processes is ideal</b> for medium-traffic sites. If this number is always zero, your users are likely experiencing "cold start" delays on their requests.',
        ],
        'actions' => [
            [
                'case' => 'If the number of idle processes is frequently zero',
                'actions' => [
                    'Increase <code>pm.min_spare_servers</code> and <code>pm.start_servers</code> in your FPM pool configuration. This ensures a larger "safety net" of workers is always ready.',
                    'Consider switching to <code>pm = static</code> if your server is dedicated to PHP; this keeps a fixed number of workers alive at all times, providing the most consistent performance.',
                ],
            ],
            [
                'case' => 'If you have a very high number of idle processes (e.g., > 50) and RAM is tight',
                'actions' => [
                    'You might be wasting memory. Reduce <code>pm.max_spare_servers</code> to release RAM back to the operating system or the Database Buffer Pool.',
                ],
            ],
        ],
        'good_to_know' => [
            'If you use <code>pm = ondemand</code>, the number of idle processes will often drop to zero during quiet periods to save memory. While efficient for low-traffic sites, it adds a small delay to the "first" visitor who wakes the server back up.',
        ],
    ],
    Metric::PhpFpmMaxChildrenReached->value => [
        'definitions' => [
            'This is a cumulative counter showing how many times the PHP-FPM pool has reached its "pm.max_children" limit since the last service restart.',
            'It represents a "Capacity Overflow" event.',
        ],
        'why_it_matters' => [
            'Once you hit the max children limit, PHP-FPM cannot handle more concurrent requests. New connections are moved to a "Listen Queue." If that queue also fills up, the web server (Nginx/Apache) will immediately return a "502 Bad Gateway" or "504 Gateway Timeout" to your users. This is a critical indicator that your current configuration is insufficient for your traffic.',
        ],
        'how_to_read' => [
            '<b>Ideally, this should always be zero.</b> If you see this number increasing, even slowly, it means your server is struggling during peak hours or during specific heavy tasks.',
        ],
        'actions' => [
            [
                'case' => 'If this value is greater than zero',
                'actions' => [
                    'Increase the <code>pm.max_children</code> value in your pool configuration. Ensure you have enough RAM to support more workers (roughly 50MB per child on average).',
                    'Check for "Long-Lived Requests": a single slow endpoint (like a PDF generator or a heavy export) can "eat" all your workers. Optimizing the performance of these scripts is often better than simply adding more workers.',
                ],
            ],
            [
                'case' => 'If the value spikes suddenly',
                'actions' => [
                    'Look for a "Congestion Event." This often happens when the database becomes slow; because the database is slow, PHP workers stay active longer, which leads to the pool reaching its limit rapidly.',
                ],
            ],
        ],
        'good_to_know' => [
            'When this limit is reached, you will often find a warning in your PHP-FPM error logs: "server reached pm.max_children, considering raising it." Monitoring this metric allows you to react before your users start seeing error pages.',
        ],
    ],
    Metric::PhpFpmMemoryPeakPercent->value => [
        'definitions' => [
            'This represents the highest amount of RAM used by a single PHP request, expressed as a percentage of your "memory_limit" setting.',
            'It tracks the "worst-case scenario" for memory consumption during the lifecycle of a request.',
        ],
        'why_it_matters' => [
            'PHP is very strict with memory. If a script exceeds 100% of the limit, it crashes immediately. High peaks are early warning signs: even if your site works now, a slightly larger data set (like a user uploading a bigger photo or a larger search result) could trigger a crash for your users.',
        ],
        'how_to_read' => [
            '<b>Aim for peaks below 70%.</b> This provides a safety buffer for unexpected data spikes. If your peak is consistently at 95%+, you are one small variable away from a broken production environment.',
        ],
        'actions' => [
            [
                'case' => 'If the memory peak is consistently high (> 80%)',
                'actions' => [
                    'Identify "Memory Leaks" or heavy objects. In frameworks like Laravel, using <code>->get()</code> on a massive table instead of <code>->chunk()</code> or <code>->cursor()</code> is the #1 cause of memory peaks.',
                    'Check for large file processing. If you are reading files into memory with <code>file_get_contents()</code>, switch to "Streams" to keep the memory footprint low.',
                ],
            ],
            [
                'case' => 'If you frequently hit 100% (Fatal Errors)',
                'actions' => [
                    'Do not just increase the <code>memory_limit</code> globally. First, find the specific script causing the crash. Increasing the limit globally can allow "runaway" scripts to starve the entire server of RAM, crashing MySQL or other services.',
                    'If the script is legitimately heavy (e.g., generating a complex report), increase the limit only for that specific route or task using <code>ini_set("memory_limit", "512M")</code>.',
                ],
            ],
        ],
        'good_to_know' => [
            'Memory usage in PHP doesn\'t always return to the OS immediately after a script finishes; FPM workers reuse memory. However, this metric specifically measures the <em>peak</em> within a single request, which is the most critical threshold for stability.',
        ],
    ],
    Metric::PhpFpmReqPerSec->value => [
        'definitions' => [
            'This represents the average number of PHP requests successfully processed by the FPM pool every second.',
            'It is the primary measure of "Throughput" for your web application.',
        ],
        'why_it_matters' => [
            'Monitoring your "Req/Sec" allows you to distinguish between a traffic problem and a performance problem. For example, if your CPU usage is high but your Req/Sec is low, it means your code has become inefficient. Conversely, if both are high, you simply have more customers and might need to scale up your infrastructure.',
        ],
        'how_to_read' => [
            'Watch for the "Normal Baseline" of your app. Every application has a unique signature (e.g., higher traffic at 10 AM). Deviations from this pattern are usually the first sign of an external event—either a successful marketing campaign or a malicious bot attack.',
        ],
        'actions' => [
            [
                'case' => 'If Requests per Second spike suddenly without an obvious reason',
                'actions' => [
                    'Check your access logs for "Bot Behavior." A sudden surge in requests to the same URL often indicates a scraper or a brute-force attack. Consider implementing rate-limiting or using a WAF (Web Application Firewall).',
                    'Verify if a recently released feature is triggering recursive or unnecessary AJAX calls from the frontend.',
                ],
            ],
            [
                'case' => 'If Requests per Second drop while CPU usage stays high',
                'actions' => [
                    'This is a "Stall" condition. Your server is working hard but finishing very little. This usually happens when queries are locking up or scripts are timing out before completion. Investigate your "Slow Query" logs immediately.',
                ],
            ],
        ],
        'good_to_know' => [
            'In a high-performance setup (like FrankenPHP or optimized FPM), your Req/Sec should scale linearly with your traffic until you hit a hardware bottleneck (usually CPU or Database I/O). If it flattens out while users are still trying to connect, you have reached your concurrency limit.',
        ],
    ],

    Metric::PhpFpmSlowRequest->value => [
        'definitions' => [
            'This counts the number of PHP requests that took longer to complete than the "request_slowlog_timeout" set in your FPM configuration.',
            'It serves as a "User Frustration Index" for your application performance.',
        ],
        'why_it_matters' => [
            'A slow request is often more dangerous than a failed one. It ties up a PHP worker for a long period, which can lead to a "pile-up" effect (clogging your FPM pool) and eventually crashing the whole server. Tracking when these happen allows you to correlate slowness with external factors like high traffic or background cron jobs.',
        ],
        'how_to_read' => [
            '<b>Target: Zero.</b> In a high-performance app, requests should ideally finish in under 200ms-500ms. If you see persistent spikes here, your users are experiencing "lag," which directly impacts conversion rates and SEO.',
        ],
        'actions' => [
            [
                'case' => 'If slow requests increase during specific hours',
                'actions' => [
                    'Check for "Noisy Neighbors" or scheduled tasks. You might have a heavy cron job or a backup running at the same time that is stealing CPU cycles or Disk I/O from your PHP processes.',
                    'Check for external API latency. If your script waits for a 3rd party service (like a shipping calculator or payment gateway) that is having issues, your request will be flagged as slow.',
                ],
            ],
            [
                'case' => 'If slow requests appear only on specific URLs',
                'actions' => [
                    'Enable the PHP-FPM "slowlog". It will capture a stack trace of exactly where the script was stuck (e.g., a specific function or a database call). This is the fastest way to find the "bottleneck" in your code.',
                ],
            ],
        ],
        'good_to_know' => [
            'The default <code>request_slowlog_timeout</code> is often disabled or set too high (e.g., 5s or 10s). For a modern, snappy application, we recommend setting this to 1s or 2s to catch performance regressions early.',
        ],
    ],
    Metric::PhpApcuMemoryUsage->value => [
        'definitions' => [
            'This indicates the percentage of the allocated shared memory (shm_size) currently used by APCu to store user data.',
            'It represents the utilization of your application\'s primary "In-Memory" key-value store.',
        ],
        'why_it_matters' => [
            'Unlike OPcache, which stores code, APCu stores your application data. If this memory reaches 100%, APCu enters a "Critical State": it may delete all cached items to free up space (cache slam), or fail to store new data. This causes your app to fall back to slow database queries, leading to a sudden and massive drop in performance.',
        ],
        'how_to_read' => [
            '<b>Target: 50% to 80% usage.</b> You want enough data in the cache to be useful, but enough free space to avoid fragmentation and emergency evictions.',
        ],
        'actions' => [
            [
                'case' => 'If usage is consistently near 100%',
                'actions' => [
                    'Increase <code>apc.shm_size</code> in your php.ini. For modern frameworks, 64MB or 128MB is a common starting point, but high-traffic apps may need significantly more.',
                    'Review what you are caching. APCu is for "hot data" (frequently accessed). Do not store large blobs or thousands of rarely used items here; use Redis for larger, persistent datasets.',
                ],
            ],
            [
                'case' => 'If usage stays very low (e.g., < 10%) while the app is active',
                'actions' => [
                    'Check if your application is actually utilizing APCu. You might have the extension enabled but your framework (Laravel, Symfony) might not be configured to use it as the cache driver.',
                ],
            ],
        ],
        'good_to_know' => [
            'APCu is "per-server" memory. If you have multiple servers, they don\'t share this cache. If you need a shared cache across multiple nodes, you should be looking at Redis instead of APCu.',
        ],
    ],
    Metric::PhpApcuHitRate->value => [
        'definitions' => [
            'This represents the percentage of times PHP successfully retrieved data from the APCu cache compared to the total number of cache lookups.',
            'It is the primary efficiency score for your application-level caching strategy.',
        ],
        'why_it_matters' => [
            'Every "Miss" means your application had to perform "real work"—like running a complex database query, parsing a file, or calling an external API. If your Hit Rate is low, you are wasting CPU cycles and increasing response times. A high hit rate ensures that the most frequent and expensive operations in your code are bypassed almost instantly.',
        ],
        'how_to_read' => [
            '<b>Aim for > 95% for stable data.</b> If you see a low hit rate (below 80%), it usually means your cache is too small (causing older data to be kicked out) or your "Time to Live" (TTL) is too short, forcing the app to refresh data more often than necessary.',
        ],
        'actions' => [
            [
                'case' => 'If the Hit Rate is low despite having plenty of free APCu memory',
                'actions' => [
                    'Review your Cache Keys. If your keys are too unique (e.g., including a timestamp or a user ID where it\'s not needed), the data will rarely be reused.',
                    'Check your TTL (Time to Live). If you set your data to expire in 60 seconds but users only request it every 10 minutes, you will never achieve a high hit rate.',
                ],
            ],
            [
                'case' => 'If the Hit Rate drops suddenly after a deployment',
                'actions' => [
                    'You might have changed the logic of what you cache, or a new feature might be clearing the cache too aggressively. Check for calls to <code>apcu_clear_cache()</code> in your code.',
                ],
            ],
        ],
        'good_to_know' => [
            'A 100% hit rate isn\'t always perfect—it might mean you are never updating your data! However, in production, you should see a very steady line. If the line is "jagged" (constantly going up and down), it indicates "Cache Churn," where data is being deleted and recreated too frequently.',
        ],
    ],
    Metric::RedisVersion->value => [
        'definitions' => [
            'Redis is an open-source in-memory data store, commonly used as a cache, session store, or message broker.',
            'Endoflife timeline: <a href="https://endoflife.date/redis" target="_blank">https://endoflife.date/redis</a>.',
        ],
        'why_it_matters' => [
            'Redis is a critical piece of infrastructure. Older versions (like 5.x or earlier) lack modern security features and efficient multi-threading capabilities. Upgrading to newer versions (7.x+) often introduces significant performance boosts, such as improved I/O multithreading and the "Redis Functions" engine, which can reduce the load on your web server.',
        ],
        'good_to_know' => [
            'Redis follows a predictable versioning scheme. Even-numbered major versions (6.0, 7.0) are stable releases. Always aim for the latest "point release" (e.g., 7.2.x) to ensure you have the most recent bug fixes without the risks of a major architectural shift.',
        ],
    ],
    Metric::RedisHitRate->value => [
        'definitions' => [
            'This represents the percentage of GET requests where Redis successfully found and returned the data (Hit) compared to the total number of lookup attempts (Hits + Misses).',
            'It measures the "Efficiency Factor" of your shared memory infrastructure.',
        ],
        'why_it_matters' => [
            'A high hit rate is what makes your app scale. Every "Miss" forces your application to fall back to a slower source, usually the primary Database. If your hit rate drops significantly, your database might suddenly experience a "thundering herd" of traffic it isn\'t designed to handle, leading to a total site outage.',
        ],
        'how_to_read' => [
            '<b>Aim for > 80% for general caching.</b> If you use Redis for Session storage, the hit rate should be closer to <b>99%</b>. If it drops below 50%, you are effectively doubling your latency for half of your requests without any benefit.',
        ],
        'actions' => [
            [
                'case' => 'If the Hit Rate is consistently low or dropping',
                'actions' => [
                    'Check for "Cache Eviction": If Redis is out of RAM, it will delete old keys to make room for new ones (check your <code>maxmemory-policy</code>). This causes a "Recycling" loop that kills your hit rate.',
                    'Check your "Time to Live" (TTL) strategy. If data expires faster than it is requested, it will always result in a miss. Increase your TTL for data that doesn\'t change often.',
                ],
            ],
            [
                'case' => 'If the Hit Rate spikes to 0% suddenly',
                'actions' => [
                    'This usually indicates a "Cold Cache." Redis might have restarted without persistence (RDB/AOF), or someone executed <code>FLUSHALL</code>. Monitor your "Keys Count" to see if the database was wiped.',
                    'Check if a recent deployment changed the "Cache Key Prefix," rendering all existing data in Redis unreachable by the new code.',
                ],
            ],
        ],
        'good_to_know' => [
            'Not all misses are bad. For example, checking if a user is "Banned" will naturally result in a "Miss" for 99% of your users. However, for "hot" data like product details or settings, a miss should be rare. Always analyze hit rates based on what you are actually storing.',
        ],
    ],
    Metric::RedisMode->value => [
        'definitions' => [
            'This indicates the architectural configuration of your Redis deployment (Standalone, Sentinel, or Cluster).',
            'It defines the high-availability and sharding strategy used by your data store.',
        ],
        'why_it_matters' => [
            'The mode dictates your "Single Point of Failure." In <b>Standalone</b> mode, if the server goes down, your cache (and possibly sessions) goes with it. In <b>Sentinel</b> or <b>Cluster</b> modes, Redis can automatically promote a "follower" to "leader," ensuring your application stays online even during hardware failures. Furthermore, the mode affects how your PHP client (like PhpRedis or Predis) must be configured to connect properly.',
        ],
        'good_to_know' => [
            'Most managed cloud providers (like AWS or Google Cloud) hide this complexity behind a single endpoint, but they are usually running in <b>Cluster</b> or <b>Replication</b> mode behind the scenes. Knowing the mode helps you understand the latency overhead of network hops between nodes.',
        ],
    ],
    Metric::RedisMemoryUsage->value => [
        'definitions' => [
            'This represents the total percentage of allocated RAM currently used by Redis to store your datasets, keys, and internal metadata.',
            'It tracks how close your data store is to its physical or configured capacity.',
        ],
        'why_it_matters' => [
            'When Redis reaches its <code>maxmemory</code> limit, it triggers its eviction policy. Depending on your settings, it will either start deleting old keys (which tanks your Hit Rate) or return "OOM" errors, causing your application to crash. Additionally, high memory usage on a server with low total RAM can trigger the OS "OOM Killer," which might abruptly terminate the Redis process entirely.',
        ],
        'how_to_read' => [
            '<b>Aim for 50% to 75% usage.</b> This leaves enough "headroom" for Redis to perform background tasks like saving snapshots (RDB) or rewriting logs (AOF), which can momentarily double the memory footprint due to "copy-on-write" mechanisms.',
        ],
        'actions' => [
            [
                'case' => 'If usage is consistently above 85%',
                'actions' => [
                    'Check your <code>maxmemory-policy</code>. If it is set to <code>noeviction</code>, your app will start failing. If set to <code>allkeys-lru</code>, your database is safe but your cache efficiency will drop.',
                    'Use the <code>MEMORY USAGE <key></code> or <code>BIGKEYS</code> command in the Redis CLI to find out which specific data structures (like large Hashes or Sets) are eating your RAM.',
                    'Scale up your instance or implement Sharding (Redis Cluster) to distribute the memory load.',
                ],
            ],
            [
                'case' => 'If you see a sudden, sharp spike in memory',
                'actions' => [
                    'Check for "Key Leaks"—data being written without a TTL (Time To Live). If you forget to set an expiration on keys, Redis will grow indefinitely until it crashes.',
                ],
            ],
        ],
        'good_to_know' => [
            'Redis doesn\'t always release memory back to the Operating System immediately after you delete keys (Fragmentation). If <code>mem_fragmentation_ratio</code> is high, Redis might be taking up more system RAM than the data actually requires. You may need to trigger an "Active Defragmentation" or restart the service.',
        ],
    ],
    Metric::RedisMemoryPeak->value => [
        'definitions' => [
            'This represents the maximum amount of RAM Redis has consumed at any single point since the process was started.',
            'It serves as the "High Water Mark" for your data store\'s memory footprint.',
        ],
        'why_it_matters' => [
            'Redis is a persistent service, but memory usage is volatile. If your current usage looks safe (e.g., 40%) but your peak is at 95%, you are living on the edge. It means that during certain events, your server almost ran out of RAM. Knowing this peak allows you to provision enough hardware to survive your busiest moments, not just your quietest ones.',
        ],
        'how_to_read' => [
            '<b>Compare Peak vs. Current.</b> If the gap is massive, you have "Burst" behavior. If the peak is near your <code>maxmemory</code> limit, you likely experienced silent data evictions in the past, even if everything looks fine now.',
        ],
        'actions' => [
            [
                'case' => 'If the Peak is significantly higher than Current usage',
                'actions' => [
                    'Investigate "Bulk Operations." Do you have a daily export, a massive database sync, or a cache-warming script? These temporary spikes can crash other services on the same server if not planned for.',
                    'Check for "Temporary Keys." Some processes might be creating large intermediate Sets or Lists that are deleted after use, but still push the memory to its limit during execution.',
                ],
            ],
            [
                'case' => 'If the Peak is at or very near your maxmemory limit',
                'actions' => [
                    'Assume that your cache has already "leaked" or "thrashed." When the peak hits the limit, Redis starts deleting keys. You should increase your memory allocation immediately to ensure your peak load doesn\'t result in a performance drop.',
                ],
            ],
        ],
        'good_to_know' => [
            'You can reset this counter without restarting Redis by using the <code>MEMORY MALLOC-STATS</code> or <code>CONFIG RESETSTAT</code> commands (depending on your version). This is useful to see if new optimizations have successfully lowered your peak usage during the next traffic cycle.',
        ],
    ],
    Metric::RedisRequestsPerSecond->value => [
        'definitions' => [
            'This represents the total number of commands (GET, SET, DEL, etc.) that the Redis server is processing every second.',
            'It is the primary measure of "Transactional Throughput" for your shared data layer.',
        ],
        'why_it_matters' => [
            'Redis is incredibly fast, often handling 100k+ RPS. However, because it processes commands sequentially in a single main thread, a massive spike in requests—or a few "expensive" commands—can cause a queue to form. This leads to increased latency across your entire app, as every PHP worker waits in line for Redis to respond.',
        ],
        'how_to_read' => [
            '<b>Establish your "Quiet" vs "Peak" baseline.</b> A sudden 10x jump in RPS without a corresponding jump in web traffic usually indicates a code bug (like an infinite loop or a missing cache check) rather than real user growth.',
        ],
        'actions' => [
            [
                'case' => 'If RPS spikes suddenly but Web Traffic is stable',
                'actions' => [
                    'Check for "Cache Stampede": If a popular key expires, hundreds of PHP processes might try to re-compute and write it at the exact same second.',
                    'Use the <code>MONITOR</code> command (briefly!) in the Redis CLI to see the stream of incoming commands and identify which keys or commands are flooding the server.',
                ],
            ],
            [
                'case' => 'If RPS is high and Redis CPU usage is also near 100%',
                'actions' => [
                    'Identify and eliminate O(N) commands. Commands like <code>KEYS *</code>, <code>SMEMBERS</code>, or large <code>HGETALL</code> are "blocking" and will stop Redis from processing other requests while they run.',
                    'Implement "Pipelining" in your PHP code. This allows you to send multiple commands in a single network packet, significantly reducing the overhead per request.',
                ],
            ],
        ],
        'good_to_know' => [
            'RPS is not just about your code. Internal Redis tasks like "Replication" or "AOF rewriting" can generate internal activity. If you see high RPS with zero application traffic, check if your "Follower" nodes are performing a full resynchronization.',
        ],
    ],

    Metric::SymfonyVersion->value => [
        'definitions' => [
            'Symfony is the PHP framework used to build your application.',
            'Official timeline: <a href="https://symfony.com/releases" target="_blank">https://symfony.com/releases</a>.',
            'Endoflife timeline: <a href="https://endoflife.date/symfony" target="_blank">https://endoflife.date/symfony</a>.',
        ],
        'why_it_matters' => [
            'Each Symfony version has a specific window for "Bug Fixes" and "Security Fixes." Once a version reaches End of Life (EOL), any newly discovered vulnerability in the framework will remain unpatched on your server. Furthermore, modern PHP versions often require modern Symfony versions to remain compatible and performant.',
        ],
        'good_to_know' => [
            'Long Term Support (LTS) versions (like 4.4, 5.4, 6.4, 7.4) are the safest choice for most production environments, as they provide 3 years of bug fixes and 4 years of security patches.',
        ],
    ],
    Metric::SymfonyEnvironment->value => [
        'definitions' => [
            'This indicates the active configuration profile (APP_ENV) and the status of the "Debug" flag (APP_DEBUG).',
            'Debug mode provides detailed error pages and additional logs to help developers understand issues.',
        ],
        'why_it_matters' => [
            'In <b>Production</b>, Symfony is optimized for speed: it caches everything and hides technical error details. In <b>Development</b>, Symfony prioritizes information: it recompiles the cache on every request and provides a detailed "Profiler." Running a production site in "dev" mode can make it 10x slower and, more importantly, might expose your database credentials or source code to visitors via detailed error pages.',
        ],
        'how_to_read' => [
            '“dev” and “prod” are common names, but they can be customized and other environments may exist.',
            'Debug mode should be disabled in production to avoid performance impact and exposing sensitive information.',
        ],
        'good_to_know' => [
            'The Symfony Profiler (the toolbar at the bottom of the screen) is only available when <code>APP_DEBUG</code> is true. If you can\'t see it during development, check this metric first. Conversely, if you <em>can</em> see it in production, your server is currently insecure.',
        ],
    ],
    Metric::SymfonyFlex->value => [
        'definitions' => [
            'Symfony Flex is a tool that automates the installation and configuration of packages. Each package can provide a “recipe”, which adds or updates configuration files in your project.',
            'This metric tracks whether your configuration files are in sync with the latest official best practices.',
        ],
        'why_it_matters' => [
            'When you install a package via Composer, Symfony Flex uses a recipe to create the necessary boilerplate. Over time, these recipes are improved to fix security issues, improve performance, or adapt to new Symfony features. If you don\'t update your recipes, your configuration becomes "stale," making future framework upgrades much harder and potentially leaving your app with sub-optimal settings.',
        ],
        'actions' => [
            [
                'case' => 'If recipes are marked as "Outdated"',
                'actions' => [
                    'Run <code>composer recipes:update</code> in your terminal. This will show you the differences (diff) between your current config and the new version.',
                    'Review the changes carefully. Since these are configuration files, you need to ensure the update doesn\'t overwrite your custom settings (like specific API keys or database naming conventions).',
                ],
            ],
        ],
        'good_to_know' => [
            'Updating recipes is essentially "Refactoring your Config." It is a best practice to do this one package at a time and commit the changes to Git immediately so you can revert easily if a new configuration breaks your environment.',
        ],
    ],

    Metric::SymfonyMessenger->value => [
        'definitions' => [
            'Symfony Messenger is a component that helps handle asynchronous tasks by sending messages to queues (called transports). These messages are processed later by workers.',
        ],
        'why_it_matters' => [
            'A growing queue is a sign of a "Bottleneck." If messages pile up, your users might experience delays—for example, an order confirmation email arriving 20 minutes late. If the queue grows indefinitely, your workers may be crashed, stuck in an infinite loop, or simply too slow to keep up with the incoming traffic.',
        ],
        'how_to_read' => [
            'Each transport represents a queue. The number shown is how many messages are waiting to be processed.',
            'Some transports may be marked as uncountable. This means the system cannot determine how many messages are in the queue, depending on the transport type or configuration.',
            'Many setups include a failed transport. It stores messages that could not be processed due to errors, so they can be reviewed or retried later.',
        ],
        'actions' => [
            [
                'case' => 'If a specific transport is accumulating messages',
                'actions' => [
                    'Verify your workers are active. Use a process manager like <b>Supervisor</b> or <b>Systemd</b> to ensure <code>php bin/console messenger:consume</code> is always running.',
                    'Scale horizontally: If the worker is running but can\'t keep up, start additional worker processes to handle the messages in parallel.',
                ],
            ],
            [
                'case' => 'If messages appear in the "failed" transport',
                'actions' => [
                    'Investigate the "Slow Death": Run <code>php bin/console messenger:failed:show</code>. Often, this is caused by external API timeouts or database deadlocks.',
                    'Once the underlying issue is fixed, use <code>php bin/console messenger:failed:retry</code> to put the messages back into the main queue.',
                ],
            ],
            [
                'case' => 'If you need deeper visibility into your message queues',
                'actions' => [
                    'Consider installing <a href="https://github.com/zenstruck/messenger-monitor-bundle" target="_blank">zenstruck/messenger-monitor-bundle</a>, a Composer package that provides a monitoring interface for Symfony Messenger transports and workers.',
                ],
            ],
        ],
        'good_to_know' => [
            'Messenger is not just for background tasks; it can also be used for "Command Bus" patterns to keep your code clean. For high-volume production apps, we highly recommend using Redis or RabbitMQ as your transport rather than the database (<code>doctrine</code>) to avoid unnecessary DB load.',
        ],
    ],
    Metric::SymfonySchedulerNextTask->value => [
        'definitions' => [
            'Symfony Scheduler is a component that allows you to define and run scheduled tasks (like cron jobs) directly in your application.',
            'It works together with the Messenger component to dispatch and process these tasks.',
        ],
        'why_it_matters' => [
            'It helps automate recurring tasks such as sending emails, cleaning data, or running background jobs. Knowing the next scheduled task helps ensure everything is running as expected.',
        ],
        'how_to_read' => [
            'This card shows the next task that will be executed and when it is scheduled to run.',
        ],
        'good_to_know' => [
            'The Scheduler is "Stateful." It calculates the next run time based on the last successful execution. To ensure high availability in production, it is recommended to use a shared <b>Cache</b> (like Redis) so the scheduler can track its state even if the server restarts.',
        ],
    ],

    Metric::SymfonySchedulerTasks->value => [
        'definitions' => [
            'This provides a chronological list of all upcoming recurring tasks managed by the Symfony Scheduler.',
            'It serves as the "Future Roadmap" for your application\'s internal automation.',
        ],
        'why_it_matters' => [
            'Visualizing the upcoming queue helps you detect "Scheduling Collisions." If you have ten heavy tasks (like generating reports or syncing large datasets) all scheduled for exactly 12:00 AM, they will fight for CPU and Database resources simultaneously. This can cause the server to lag or crash. Seeing the order of tasks allows you to stagger them for better stability.',
        ],
        'how_to_read' => [
            'This card shows the next scheduled tasks in execution order.',
        ],
        'good_to_know' => [
            'The scheduler relies on a "Tick" system. Every time a worker runs, it checks this list to see what is due. If your list looks correct but nothing is actually running, the issue lies with your <b>Messenger Workers</b>, not the schedule definition itself.',
        ],
    ],
];
