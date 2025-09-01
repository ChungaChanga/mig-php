<?php
namespace Andrey\PhpMig;
use Braintree\Util;
use Braintree\Xml;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class BraintreeCustomerFetcher
{
    private Client $client;
    private string $query;
    private int $pageSize;

    public function __construct(string $env, string $publicKey, string $privateKey, int $pageSize = 50)
    {
        $baseUri = match ($env) {
            'production' => 'https://payments.braintree-api.com/graphql',
            'sandbox'    => 'https://payments.sandbox.braintree-api.com/graphql',

            default      => throw new \InvalidArgumentException("Unknown environment: $env"),
        };

        $stack = HandlerStack::create();
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            error_log(strval($request->getMethod(). ' '. $request->getUri()) );
            return $request;
        }));

        $this->client = new Client([
            'base_uri' => $baseUri,
            'headers'  => [
                'Content-Type'      => 'application/json',
                'X-ApiVersion' => '6',
                'Authorization'     => 'Basic ' . base64_encode("$publicKey:$privateKey"),
            ],
           // 'handler' => $stack
        ]);

        $this->pageSize = $pageSize;

        $this->query = <<<GQL
        query Customers(\$input: CustomerSearchInput!, \$after: String) {
          search {
            customers(input: \$input, after: \$after) {
              edges {
                node {
                  id
                  firstName
                  legacyId
                  lastName
                  company
                }
              }
              pageInfo {
                hasNextPage
                endCursor
              }
            }
          }
        }
        GQL;
    }

    public function yieldAllCustomers(): \Generator
    {
        $after = null;

        do {
            $vars = [
                'input' => new \stdClass(),
            ];
            if ($after) {
                $vars['after'] = $after;
            }

            $res = $this->client->post('', [
                'json' => ['query' => $this->query, 'variables' => $vars]
            ]);

            $data = json_decode($res->getBody(), true);
            error_log(count($data['data']['search']['customers'], true));
            if (isset($data['errors'])) {
                $messages = array_map(fn($e) => $e['message'] ?? json_encode($e), $data['errors']);
                throw new \RuntimeException("GraphQL errors: " . implode("; ", $messages));
            }

            $customers = $data['data']['search']['customers'] ?? null;
            if (!$customers) {
                break;
            }

            foreach ($customers['edges'] ?? [] as $edge) {
                yield $edge['node'];
            }

            $after   = $customers['pageInfo']['endCursor'] ?? null;
            $hasNext = $customers['pageInfo']['hasNextPage'] ?? false;

        } while ($hasNext);
    }

    public function yieldCustomersByIdPrefix(string $prefix): \Generator
    {
        foreach ($this->yieldAllCustomers() as $customer) {
            if (isset($customer['legacyId']) && str_starts_with($customer['legacyId'], $prefix)) {
                yield $customer;
            }
        }
    }

    public function getCustomersCount(): int
    {
        $query = <<<GQL
    query CustomersCount(\$input: CustomerSearchInput!) {
      search {
        customers(input: \$input) {
          totalCount
        }
      }
    }
    GQL;

        $vars = [
            'input' => new \stdClass(), // пустой фильтр = все кастомеры
        ];

        $res = $this->client->post('', [
            'json' => ['query' => $query, 'variables' => $vars]
        ]);

        $data = json_decode($res->getBody(), true);

        if (isset($data['errors'])) {
            $messages = array_map(fn($e) => $e['message'] ?? json_encode($e), $data['errors']);
            throw new \RuntimeException("GraphQL errors: " . implode("; ", $messages));
        }

        return $data['data']['search']['customers']['totalCount'] ?? 0;
    }

    public function search($host, $merchantId, $prefix, $page, $perPage = 10)
    {
        error_log("page: $page");
        $logger = new Logger('curl1.log');
        $jsonBody = [
            'search' => [
                'id' => ['starts_with' => $prefix],
            ],
            'page' => $page,
        ];
        $res = $this->client->post("https://$host/merchants/$merchantId/customers/advanced_search", [
            'json' => $jsonBody
        ]);

        $logger->log($res->getBody()->getContents());
        die;
        if ($res->getStatusCode() === 200 || $res->getStatusCode() === 201
            || $res->getStatusCode() === 422 || $res->getStatusCode() == 400)
        {
            $res = $res->getBody()->getContents();
            $res = Xml::buildArrayFromXml($res);
        } else {
            Util::throwStatusCodeException($res->getStatusCode());
        }



        $customers = Util::extractattributeasarray(
            $res['customers'],
            'customer'
        );
        $ids = array_map(fn($customer) => $customer->id, $customers);
        $currentPage =  $res['customers']['currentPageNumber'][0];
        $logger->log("currentPageNumber: $currentPage");
        $logger->log($ids);
        error_log(print_r("currentPageNumber: $currentPage", true));
        return $customers;

//        $xml = $res->getBody()->getContents();
//        $simplexml = simplexml_load_string($xml);
//        $obj = json_decode(json_encode((array)$simplexml), true);
//        return $obj;
    }
}
