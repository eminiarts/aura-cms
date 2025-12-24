# Flows in Aura CMS (Planned Feature)

> **Note**: The Flows feature is planned for a future release of Aura CMS and is not yet implemented. This documentation provides a preview of the planned functionality and serves as a design specification.

Flows will provide a powerful workflow automation system that allows you to create visual, event-driven automations for your content and business processes. Think of it as Zapier or n8n built directly into your CMS.

## Table of Contents

- [Overview](#overview)
- [Current Status](#current-status)
- [Core Concepts](#core-concepts)
- [Flow Builder](#flow-builder)
- [Triggers](#triggers)
- [Actions](#actions)
- [Conditions](#conditions)
- [Variables & Data](#variables--data)
- [Examples](#examples)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)
- [Planned Features](#planned-features)
- [Integration Points](#integration-points)

## Overview

Flows will enable you to:
- **Automate repetitive tasks** without writing code
- **Connect resources** with sophisticated workflows
- **React to events** in real-time
- **Integrate with external services** via webhooks and APIs
- **Schedule automated processes** with cron-like precision

## Current Status

The Flows feature is in the **design phase**. The codebase contains:

- **Infrastructure hooks** in the BulkActions trait for calling flows via `callFlow.{flowId}`
- **Placeholder code** in Resource classes for manual flow triggers
- **Test group configuration** for future flow tests

The core Flow models, resources, Livewire components, and database migrations have not yet been implemented.

## Core Concepts

### What is a Flow?

A Flow will be a visual representation of an automated workflow consisting of:
- **Trigger**: What starts the flow
- **Actions**: What the flow does
- **Conditions**: Logic that controls the flow path
- **Variables**: Data that passes through the flow

### Planned Flow Model

```php
namespace Aura\Base\Resources;

use Aura\Base\Resource;

class Flow extends Resource
{
    public static string $type = 'Flow';
    
    protected static ?string $slug = 'flow';

    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Name',
                'slug' => 'name',
                'validation' => 'required|max:255',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'name' => 'Description',
                'slug' => 'description',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Select',
                'name' => 'Status',
                'slug' => 'status',
                'options' => [
                    ['key' => 'draft', 'value' => 'Draft'],
                    ['key' => 'active', 'value' => 'Active'],
                    ['key' => 'paused', 'value' => 'Paused'],
                ],
            ],
            // trigger and actions stored as JSON in meta
        ];
    }
}
```

## Flow Builder

### Visual Interface (Planned)

The Flow Builder will provide a drag-and-drop interface for creating workflows:

```javascript
// Conceptual Flow Builder component
const FlowBuilder = {
    nodes: [
        {
            id: 'trigger-1',
            type: 'trigger',
            data: {
                event: 'resource.created',
                resource: 'Article',
                conditions: {
                    status: 'published'
                }
            }
        },
        {
            id: 'action-1',
            type: 'action',
            data: {
                action: 'send_email',
                to: '{{ author.email }}',
                template: 'article_published'
            }
        }
    ],
    edges: [
        {
            source: 'trigger-1',
            target: 'action-1'
        }
    ]
};
```

### Flow Definition Structure

```php
// Example flow definition
$flow = [
    'name' => 'Publish Article Workflow',
    'description' => 'Automate article publishing process',
    'trigger' => [
        'type' => 'resource.updated',
        'resource' => 'Article',
        'conditions' => [
            'field' => 'status',
            'operator' => 'changed_to',
            'value' => 'published'
        ]
    ],
    'actions' => [
        [
            'type' => 'send_notification',
            'user' => '{{ author }}',
            'message' => 'Your article "{{ title }}" has been published!'
        ],
        [
            'type' => 'webhook',
            'url' => 'https://api.example.com/articles',
            'method' => 'POST',
            'data' => [
                'title' => '{{ title }}',
                'url' => '{{ url }}',
                'author' => '{{ author.name }}'
            ]
        ],
        [
            'type' => 'wait',
            'duration' => '5 minutes'
        ],
        [
            'type' => 'send_tweet',
            'message' => 'New article: {{ title }} {{ url }}'
        ]
    ]
];
```

## Triggers

### Resource Triggers

Flows can be triggered by resource events:

```php
// Available resource triggers
$triggers = [
    'resource.created' => 'When a resource is created',
    'resource.updated' => 'When a resource is updated',
    'resource.deleted' => 'When a resource is deleted',
    'resource.restored' => 'When a resource is restored',
    'resource.field_changed' => 'When a specific field changes',
];

// Example trigger configuration
[
    'type' => 'resource.field_changed',
    'resource' => 'Order',
    'field' => 'status',
    'from' => 'pending',
    'to' => 'completed'
]
```

### Schedule Triggers

Run flows on a schedule:

```php
// Schedule trigger examples
[
    'type' => 'schedule',
    'cron' => '0 9 * * MON' // Every Monday at 9 AM
]

[
    'type' => 'schedule',
    'frequency' => 'daily',
    'time' => '09:00',
    'timezone' => 'America/New_York'
]
```

### Webhook Triggers

Trigger flows via external webhooks:

```php
[
    'type' => 'webhook',
    'method' => 'POST',
    'authentication' => 'bearer_token',
    'payload_validation' => [
        'order_id' => 'required|integer',
        'status' => 'required|string'
    ]
]
```

### Manual Triggers

Allow users to manually trigger flows:

```php
[
    'type' => 'manual',
    'resource' => 'Article',
    'button_label' => 'Send to Review',
    'confirmation' => true,
    'confirmation_message' => 'Are you sure you want to send this article for review?'
]
```

## Actions

### Built-in Actions

#### Send Email
```php
[
    'type' => 'send_email',
    'to' => ['{{ author.email }}', 'editor@example.com'],
    'cc' => '{{ manager.email }}',
    'subject' => 'Article Published: {{ title }}',
    'template' => 'article_published',
    'data' => [
        'article' => '{{ $resource }}',
        'published_at' => '{{ now }}'
    ]
]
```

#### Create Resource
```php
[
    'type' => 'create_resource',
    'resource' => 'Task',
    'data' => [
        'title' => 'Review article: {{ title }}',
        'description' => 'Please review the recently published article',
        'assigned_to' => '{{ editor.id }}',
        'due_date' => '{{ now.addDays(3) }}',
        'priority' => 'high'
    ]
]
```

#### Update Resource
```php
[
    'type' => 'update_resource',
    'resource' => 'Article',
    'id' => '{{ id }}',
    'data' => [
        'reviewed_at' => '{{ now }}',
        'reviewed_by' => '{{ auth.user.id }}'
    ]
]
```

#### HTTP Request
```php
[
    'type' => 'http_request',
    'method' => 'POST',
    'url' => 'https://api.slack.com/api/chat.postMessage',
    'headers' => [
        'Authorization' => 'Bearer {{ settings.slack_token }}',
        'Content-Type' => 'application/json'
    ],
    'body' => [
        'channel' => '#content',
        'text' => 'New article published: {{ title }}'
    ]
]
```

#### Add to Queue
```php
[
    'type' => 'dispatch_job',
    'job' => 'App\\Jobs\\ProcessArticle',
    'data' => [
        'article_id' => '{{ id }}'
    ],
    'queue' => 'high',
    'delay' => 60 // seconds
]
```

### Custom Actions (Planned)

When implemented, you'll be able to register custom actions:

```php
namespace App\Flows\Actions;

use Aura\Base\Flows\Actions\Action;

class SendSmsAction extends Action
{
    public string $type = 'send_sms';
    
    public string $name = 'Send SMS';
    
    public string $description = 'Send an SMS message via Twilio';
    
    public function fields(): array
    {
        return [
            [
                'name' => 'to',
                'type' => 'text',
                'label' => 'Phone Number',
                'validation' => 'required|phone'
            ],
            [
                'name' => 'message',
                'type' => 'textarea',
                'label' => 'Message',
                'validation' => 'required|max:160'
            ]
        ];
    }
    
    public function execute(array $context, array $config): array
    {
        $to = $this->parseValue($config['to'], $context);
        $message = $this->parseValue($config['message'], $context);
        
        // Send SMS via Twilio
        $twilio = new \Twilio\Rest\Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        
        $result = $twilio->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $message
        ]);
        
        return [
            'success' => true,
            'message_sid' => $result->sid
        ];
    }
}
```

## Conditions

### If/Else Conditions

```php
[
    'type' => 'condition',
    'if' => [
        'field' => 'author.role',
        'operator' => 'equals',
        'value' => 'premium'
    ],
    'then' => [
        // Actions for premium authors
        [
            'type' => 'send_email',
            'template' => 'premium_article_published'
        ]
    ],
    'else' => [
        // Actions for regular authors
        [
            'type' => 'send_email',
            'template' => 'article_published'
        ]
    ]
]
```

### Switch Conditions

```php
[
    'type' => 'switch',
    'field' => 'article.category',
    'cases' => [
        'news' => [
            ['type' => 'add_tag', 'tag' => 'breaking-news'],
            ['type' => 'notify_channel', 'channel' => '#news']
        ],
        'tutorial' => [
            ['type' => 'add_tag', 'tag' => 'how-to'],
            ['type' => 'notify_channel', 'channel' => '#tutorials']
        ],
        'default' => [
            ['type' => 'add_tag', 'tag' => 'general']
        ]
    ]
]
```

### Loop Conditions

```php
[
    'type' => 'foreach',
    'items' => '{{ article.tags }}',
    'as' => 'tag',
    'actions' => [
        [
            'type' => 'http_request',
            'url' => 'https://api.example.com/tags',
            'method' => 'POST',
            'body' => [
                'article_id' => '{{ article.id }}',
                'tag' => '{{ tag.name }}'
            ]
        ]
    ]
]
```

## Variables & Data

### Context Variables

Flows have access to context variables:

```php
// Available in all flows
$context = [
    'resource' => $triggeredResource, // The resource that triggered the flow
    'auth' => [
        'user' => auth()->user(), // Current user (if applicable)
    ],
    'now' => now(), // Current timestamp
    'settings' => SettingsManager::all(), // System settings
];
```

### Variable Syntax

Use Blade-like syntax to access variables:

```php
// Simple variables
'{{ title }}'
'{{ author.name }}'
'{{ resource.fields.custom_field }}'

// With filters
'{{ title | upper }}'
'{{ created_at | date:"Y-m-d" }}'
'{{ price | currency:"USD" }}'

// With default values
'{{ description | default:"No description" }}'

// Conditional output
'{{ status == "published" ? "Live" : "Draft" }}'
```

### Data Transformation

Transform data within flows:

```php
[
    'type' => 'transform',
    'data' => [
        'formatted_title' => '{{ title | upper }}',
        'excerpt' => '{{ content | truncate:200 }}',
        'author_email' => '{{ author.email | lower }}',
        'tags_string' => '{{ tags | pluck:"name" | join:", " }}'
    ]
]
```

## Examples

### Example 1: Content Approval Workflow

```php
Flow::create([
    'name' => 'Content Approval Workflow',
    'trigger' => [
        'type' => 'resource.created',
        'resource' => 'Article',
        'conditions' => [
            'field' => 'status',
            'value' => 'pending_review'
        ]
    ],
    'actions' => [
        // Assign to editor
        [
            'type' => 'update_resource',
            'data' => [
                'assigned_editor' => '{{ settings.default_editor_id }}'
            ]
        ],
        // Create review task
        [
            'type' => 'create_resource',
            'resource' => 'Task',
            'data' => [
                'title' => 'Review: {{ title }}',
                'assigned_to' => '{{ settings.default_editor_id }}',
                'due_date' => '{{ now.addDays(2) }}'
            ]
        ],
        // Notify editor
        [
            'type' => 'send_notification',
            'user' => '{{ settings.default_editor_id }}',
            'title' => 'New Article for Review',
            'message' => '{{ author.name }} has submitted "{{ title }}" for review.'
        ]
    ]
]);
```

### Example 2: Order Fulfillment

```php
Flow::create([
    'name' => 'Order Fulfillment',
    'trigger' => [
        'type' => 'resource.field_changed',
        'resource' => 'Order',
        'field' => 'payment_status',
        'to' => 'completed'
    ],
    'actions' => [
        // Update order status
        [
            'type' => 'update_resource',
            'data' => [
                'fulfillment_status' => 'processing'
            ]
        ],
        // Send to warehouse API
        [
            'type' => 'http_request',
            'method' => 'POST',
            'url' => '{{ settings.warehouse_api_url }}/orders',
            'body' => [
                'order_id' => '{{ id }}',
                'items' => '{{ items }}',
                'shipping_address' => '{{ shipping_address }}'
            ]
        ],
        // Email customer
        [
            'type' => 'send_email',
            'to' => '{{ customer.email }}',
            'template' => 'order_processing'
        ],
        // Schedule follow-up
        [
            'type' => 'wait',
            'duration' => '24 hours'
        ],
        [
            'type' => 'send_email',
            'to' => '{{ customer.email }}',
            'template' => 'order_shipped'
        ]
    ]
]);
```

### Example 3: Social Media Automation

```php
Flow::create([
    'name' => 'Social Media Publishing',
    'trigger' => [
        'type' => 'schedule',
        'cron' => '0 10,14,18 * * *' // 10am, 2pm, 6pm daily
    ],
    'actions' => [
        // Get scheduled posts
        [
            'type' => 'query_resources',
            'resource' => 'SocialPost',
            'conditions' => [
                'status' => 'scheduled',
                'publish_at' => '<= {{ now }}'
            ],
            'as' => 'posts'
        ],
        // Loop through posts
        [
            'type' => 'foreach',
            'items' => '{{ posts }}',
            'as' => 'post',
            'actions' => [
                // Publish to platform
                [
                    'type' => 'switch',
                    'field' => 'post.platform',
                    'cases' => [
                        'twitter' => [
                            ['type' => 'post_to_twitter', 'message' => '{{ post.content }}']
                        ],
                        'facebook' => [
                            ['type' => 'post_to_facebook', 'message' => '{{ post.content }}']
                        ],
                        'instagram' => [
                            ['type' => 'post_to_instagram', 'image' => '{{ post.image }}', 'caption' => '{{ post.content }}']
                        ]
                    ]
                ],
                // Update status
                [
                    'type' => 'update_resource',
                    'id' => '{{ post.id }}',
                    'data' => [
                        'status' => 'published',
                        'published_at' => '{{ now }}'
                    ]
                ]
            ]
        ]
    ]
]);
```

## Advanced Features

### Error Handling

```php
[
    'type' => 'try',
    'actions' => [
        // Actions that might fail
        [
            'type' => 'http_request',
            'url' => 'https://api.example.com/webhook'
        ]
    ],
    'catch' => [
        // Error handling actions
        [
            'type' => 'log_error',
            'message' => 'Webhook failed: {{ error.message }}'
        ],
        [
            'type' => 'send_notification',
            'user' => '{{ settings.admin_id }}',
            'message' => 'Flow error: {{ flow.name }} - {{ error.message }}'
        ]
    ],
    'finally' => [
        // Always execute these actions
        [
            'type' => 'update_flow_log',
            'status' => 'completed'
        ]
    ]
]
```

### Parallel Execution

```php
[
    'type' => 'parallel',
    'actions' => [
        [
            'type' => 'send_email',
            'to' => 'customer@example.com'
        ],
        [
            'type' => 'send_sms',
            'to' => '+1234567890'
        ],
        [
            'type' => 'webhook',
            'url' => 'https://crm.example.com/api/notify'
        ]
    ]
]
```

### Sub-flows

```php
[
    'type' => 'execute_flow',
    'flow' => 'customer-onboarding',
    'data' => [
        'customer_id' => '{{ customer.id }}',
        'plan' => '{{ order.plan }}'
    ]
]
```

### Rate Limiting

```php
[
    'type' => 'rate_limit',
    'key' => 'api_calls_{{ api_name }}',
    'limit' => 100,
    'period' => '1 hour',
    'actions' => [
        // Actions to rate limit
    ],
    'exceeded' => [
        // Actions when limit exceeded
        [
            'type' => 'wait',
            'duration' => '1 hour'
        ]
    ]
]
```

## Best Practices

### 1. Keep Flows Simple

- Break complex workflows into smaller sub-flows
- Use descriptive names for flows and actions
- Add comments to explain complex logic

### 2. Handle Errors Gracefully

```php
// Always include error handling for external services
[
    'type' => 'try',
    'actions' => [
        ['type' => 'http_request', 'url' => 'https://api.example.com']
    ],
    'catch' => [
        ['type' => 'log_error'],
        ['type' => 'send_notification', 'message' => 'API call failed']
    ]
]
```

### 3. Use Variables Efficiently

```php
// Store commonly used values
[
    'type' => 'set_variables',
    'variables' => [
        'customer_name' => '{{ customer.first_name }} {{ customer.last_name }}',
        'order_total' => '{{ items | sum:"price" }}',
        'is_premium' => '{{ customer.plan == "premium" }}'
    ]
]
```

### 4. Test Flows Thoroughly

```php
// Test mode for flows
Flow::create([
    'name' => 'Test Flow',
    'mode' => 'test', // Won't execute actions, just logs
    'trigger' => [...],
    'actions' => [...]
]);
```

### 5. Monitor Performance

```php
// Add performance tracking
[
    'type' => 'measure_time',
    'name' => 'api_call_duration',
    'actions' => [
        ['type' => 'http_request', 'url' => '...']
    ]
]
```

## Planned Features

The Flows feature is planned with the following capabilities:

1. **Visual Flow Designer**: Drag-and-drop interface for building flows
2. **Flow Templates**: Pre-built flows for common use cases
3. **Advanced Debugging**: Step-through debugging and flow visualization
4. **AI Integration**: Use AI for content generation and decision making
5. **External Integrations**: Native integrations with popular services
6. **Flow Analytics**: Detailed analytics and performance metrics
7. **Version Control**: Track changes and rollback flows
8. **Collaborative Editing**: Multiple users can work on flows together

## Integration Points

The codebase already contains infrastructure for Flows integration:

### Bulk Actions Support

Resources can trigger flows on selected items via bulk actions:

```php
// In your Resource class
public function getBulkActions(): array
{
    return [
        'callFlow.1' => 'Run Approval Workflow',
        'callFlow.2' => 'Send Notifications',
    ];
}
```

The `BulkActions` trait in `src/Livewire/Table/Traits/BulkActions.php` handles the `callFlow.{flowId}` pattern.

### Manual Flow Triggers

Resources can define manual flow triggers that appear as action buttons:

```php
// Planned implementation
public function callFlow(int $flowId): void
{
    $flow = Flow::find($flowId);
    $operation = $flow->operation;

    $flowLog = $flow->logs()->create([
        'post_id' => $this->id,
        'status' => 'running',
        'started_at' => now(),
    ]);

    $operation->run($this, $flowLog->id);
}
```

---

> **Status**: This feature is in the design phase. Check the [GitHub repository](https://github.com/eminiarts/aura-cms) for updates on development progress.
> 
> Want to contribute? The design specification above outlines the planned architecture. Community contributions are welcome!