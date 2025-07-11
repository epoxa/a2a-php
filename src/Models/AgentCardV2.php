<?php

declare(strict_types=1);

namespace A2A\Models;

/**
 * A2A Protocol compliant AgentCard implementation
 * 
 * An AgentCard conveys key information:
 * - Overall details (version, name, description, uses)
 * - Skills: A set of capabilities the agent can perform
 * - Default modalities/content types supported by the agent
 * - Authentication requirements
 */
class AgentCardV2
{
    private string $name;
    private string $description;
    private string $url;
    private string $version;
    private string $protocolVersion;
    private AgentCapabilities $capabilities;
    private array $defaultInputModes;
    private array $defaultOutputModes;
    private array $skills;
    private ?AgentProvider $provider = null;
    private ?array $securitySchemes = null;
    private ?array $security = null;
    private ?array $additionalInterfaces = null;
    private ?string $preferredTransport = null;
    private ?string $documentationUrl = null;
    private ?string $iconUrl = null;
    private bool $supportsAuthenticatedExtendedCard = false;

    public function __construct(
        string $name,
        string $description,
        string $url,
        string $version,
        AgentCapabilities $capabilities,
        array $defaultInputModes,
        array $defaultOutputModes,
        array $skills,
        string $protocolVersion = '0.2.5'
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->url = $url;
        $this->version = $version;
        $this->protocolVersion = $protocolVersion;
        $this->capabilities = $capabilities;
        $this->defaultInputModes = $defaultInputModes;
        $this->defaultOutputModes = $defaultOutputModes;
        $this->skills = $skills;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function getCapabilities(): AgentCapabilities
    {
        return $this->capabilities;
    }

    public function getDefaultInputModes(): array
    {
        return $this->defaultInputModes;
    }

    public function getDefaultOutputModes(): array
    {
        return $this->defaultOutputModes;
    }

    public function getSkills(): array
    {
        return $this->skills;
    }

    public function setProvider(AgentProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function getProvider(): ?AgentProvider
    {
        return $this->provider;
    }

    public function setSecuritySchemes(array $securitySchemes): void
    {
        $this->securitySchemes = $securitySchemes;
    }

    public function setSecurity(array $security): void
    {
        $this->security = $security;
    }

    public function setAdditionalInterfaces(array $additionalInterfaces): void
    {
        $this->additionalInterfaces = $additionalInterfaces;
    }

    public function setPreferredTransport(string $preferredTransport): void
    {
        $this->preferredTransport = $preferredTransport;
    }

    public function setDocumentationUrl(string $documentationUrl): void
    {
        $this->documentationUrl = $documentationUrl;
    }

    public function setIconUrl(string $iconUrl): void
    {
        $this->iconUrl = $iconUrl;
    }

    public function setSupportsAuthenticatedExtendedCard(bool $supports): void
    {
        $this->supportsAuthenticatedExtendedCard = $supports;
    }

    public function addSkill(AgentSkill $skill): void
    {
        $this->skills[] = $skill;
    }

    public function toArray(): array
    {
        $result = [
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'version' => $this->version,
            'protocolVersion' => $this->protocolVersion,
            'capabilities' => $this->capabilities->toArray(),
            'defaultInputModes' => $this->defaultInputModes,
            'defaultOutputModes' => $this->defaultOutputModes,
            'skills' => array_map(fn(AgentSkill $skill) => $skill->toArray(), $this->skills),
            'supportsAuthenticatedExtendedCard' => $this->supportsAuthenticatedExtendedCard
        ];

        if ($this->provider !== null) {
            $result['provider'] = $this->provider->toArray();
        }

        if ($this->securitySchemes !== null) {
            $result['securitySchemes'] = $this->securitySchemes;
        }

        if ($this->security !== null) {
            $result['security'] = $this->security;
        }

        if ($this->additionalInterfaces !== null) {
            $result['additionalInterfaces'] = $this->additionalInterfaces;
        }

        if ($this->preferredTransport !== null) {
            $result['preferredTransport'] = $this->preferredTransport;
        }

        if ($this->documentationUrl !== null) {
            $result['documentationUrl'] = $this->documentationUrl;
        }

        if ($this->iconUrl !== null) {
            $result['iconUrl'] = $this->iconUrl;
        }

        return $result;
    }

    public static function fromArray(array $data): self
    {
        $capabilities = AgentCapabilities::fromArray($data['capabilities'] ?? []);
        
        $skills = [];
        if (isset($data['skills'])) {
            foreach ($data['skills'] as $skillData) {
                $skills[] = AgentSkill::fromArray($skillData);
            }
        }

        $agentCard = new self(
            $data['name'],
            $data['description'],
            $data['url'],
            $data['version'],
            $capabilities,
            $data['defaultInputModes'] ?? [],
            $data['defaultOutputModes'] ?? [],
            $skills,
            $data['protocolVersion'] ?? '0.2.5'
        );

        if (isset($data['provider'])) {
            $agentCard->setProvider(AgentProvider::fromArray($data['provider']));
        }

        if (isset($data['securitySchemes'])) {
            $agentCard->setSecuritySchemes($data['securitySchemes']);
        }

        if (isset($data['security'])) {
            $agentCard->setSecurity($data['security']);
        }

        if (isset($data['additionalInterfaces'])) {
            $agentCard->setAdditionalInterfaces($data['additionalInterfaces']);
        }

        if (isset($data['preferredTransport'])) {
            $agentCard->setPreferredTransport($data['preferredTransport']);
        }

        if (isset($data['documentationUrl'])) {
            $agentCard->setDocumentationUrl($data['documentationUrl']);
        }

        if (isset($data['iconUrl'])) {
            $agentCard->setIconUrl($data['iconUrl']);
        }

        if (isset($data['supportsAuthenticatedExtendedCard'])) {
            $agentCard->setSupportsAuthenticatedExtendedCard($data['supportsAuthenticatedExtendedCard']);
        }

        return $agentCard;
    }
}