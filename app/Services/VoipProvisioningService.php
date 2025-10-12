<?php

namespace App\Services;

use App\Models\VoiceService;
use App\Models\SipProfile;
use App\Models\VoipLine;

class VoipProvisioningService
{
    public function mapVoiceServiceToTR181(VoiceService $voiceService): array
    {
        $serviceInstance = $voiceService->service_instance ?: '1';
        $basePath = "Device.Services.VoiceService.{$serviceInstance}.";
        
        $parameters = [
            $basePath . 'Enable' => $voiceService->enabled ? 'true' : 'false',
        ];

        if ($voiceService->rtp_dscp !== null) {
            $parameters[$basePath . 'X_RTP_DSCP'] = $voiceService->rtp_dscp;
        }

        if ($voiceService->rtp_port_min && $voiceService->rtp_port_max) {
            $parameters[$basePath . 'X_RTP_PortMin'] = $voiceService->rtp_port_min;
            $parameters[$basePath . 'X_RTP_PortMax'] = $voiceService->rtp_port_max;
        }

        if ($voiceService->stun_enabled) {
            $parameters[$basePath . 'X_STUN_Enable'] = 'true';
            $parameters[$basePath . 'X_STUN_Server'] = $voiceService->stun_server;
            $parameters[$basePath . 'X_STUN_Port'] = $voiceService->stun_port;
        }

        return $parameters;
    }

    public function mapSipProfileToTR181(SipProfile $sipProfile): array
    {
        $voiceService = $sipProfile->voiceService;
        $serviceInstance = $voiceService->service_instance ?: '1';
        $profileInstance = $sipProfile->profile_instance ?: '1';
        
        $basePath = "Device.Services.VoiceService.{$serviceInstance}.VoiceProfile.{$profileInstance}.";
        
        $parameters = [
            $basePath . 'Enable' => $sipProfile->enabled ? 'true' : 'false',
            $basePath . 'Name' => $sipProfile->profile_name,
            $basePath . 'SIP.ProxyServer' => $sipProfile->proxy_server,
            $basePath . 'SIP.ProxyServerPort' => $sipProfile->proxy_port,
            $basePath . 'SIP.RegistrarServer' => $sipProfile->registrar_server,
            $basePath . 'SIP.RegistrarServerPort' => $sipProfile->registrar_port,
            $basePath . 'SIP.UserAgentDomain' => $sipProfile->domain,
            $basePath . 'SIP.UserAgentTransport' => $sipProfile->transport_protocol,
            $basePath . 'SIP.RegistrationPeriod' => $sipProfile->register_expires,
        ];

        if ($sipProfile->outbound_proxy) {
            $parameters[$basePath . 'SIP.OutboundProxy'] = $sipProfile->outbound_proxy;
            $parameters[$basePath . 'SIP.OutboundProxyPort'] = $sipProfile->outbound_proxy_port;
        }

        if ($sipProfile->codec_list) {
            $codecPath = $basePath . 'RTP.RTCP.';
            $parameters[$codecPath . 'Enable'] = 'true';
        }

        if ($sipProfile->packetization_period) {
            $parameters[$basePath . 'ServiceProviderInfo.PacketizationPeriod'] = $sipProfile->packetization_period;
        }

        if ($sipProfile->silence_suppression !== null) {
            $parameters[$basePath . 'ServiceProviderInfo.SilenceSuppression'] = $sipProfile->silence_suppression ? 'true' : 'false';
        }

        if ($sipProfile->dtmf_method) {
            $parameters[$basePath . 'DTMFMethod'] = $sipProfile->dtmf_method;
        }

        if ($sipProfile->sip_dscp !== null) {
            $parameters[$basePath . 'SIP.DSCPMark'] = $sipProfile->sip_dscp;
        }

        return $parameters;
    }

    public function mapVoipLineToTR181(VoipLine $voipLine): array
    {
        $sipProfile = $voipLine->sipProfile;
        $voiceService = $sipProfile->voiceService;
        
        $serviceInstance = $voiceService->service_instance ?: '1';
        $profileInstance = $sipProfile->profile_instance ?: '1';
        $lineInstance = $voipLine->line_instance ?: '1';
        
        $basePath = "Device.Services.VoiceService.{$serviceInstance}.VoiceProfile.{$profileInstance}.Line.{$lineInstance}.";
        
        $parameters = [
            $basePath . 'Enable' => $voipLine->enabled ? 'true' : 'false',
            $basePath . 'DirectoryNumber' => $voipLine->directory_number,
            $basePath . 'SIP.AuthUserName' => $voipLine->auth_username,
            $basePath . 'SIP.AuthPassword' => $voipLine->auth_password,
            $basePath . 'SIP.URI' => $voipLine->sip_uri,
        ];

        if ($voipLine->call_waiting_enabled !== null) {
            $parameters[$basePath . 'CallWaitingEnable'] = $voipLine->call_waiting_enabled ? 'true' : 'false';
        }

        if ($voipLine->call_forward_enabled) {
            $parameters[$basePath . 'CallingFeatures.CallForwardOnBusyEnable'] = $voipLine->call_forward_on_busy ? 'true' : 'false';
            $parameters[$basePath . 'CallingFeatures.CallForwardOnNoAnswerEnable'] = $voipLine->call_forward_on_no_answer ? 'true' : 'false';
            
            if ($voipLine->call_forward_number) {
                $parameters[$basePath . 'CallingFeatures.CallForwardOnBusyNumber'] = $voipLine->call_forward_number;
                $parameters[$basePath . 'CallingFeatures.CallForwardOnNoAnswerNumber'] = $voipLine->call_forward_number;
            }
            
            if ($voipLine->call_forward_no_answer_timeout) {
                $parameters[$basePath . 'CallingFeatures.CallForwardOnNoAnswerRingCount'] = $voipLine->call_forward_no_answer_timeout;
            }
        }

        if ($voipLine->dnd_enabled !== null) {
            $parameters[$basePath . 'CallingFeatures.DoNotDisturbEnable'] = $voipLine->dnd_enabled ? 'true' : 'false';
        }

        if ($voipLine->caller_id_enable !== null) {
            $parameters[$basePath . 'CallingFeatures.CallerIDEnable'] = $voipLine->caller_id_enable ? 'true' : 'false';
            
            if ($voipLine->caller_id_name) {
                $parameters[$basePath . 'CallingFeatures.CallerIDName'] = $voipLine->caller_id_name;
            }
        }

        if ($voipLine->anonymous_call_rejection !== null) {
            $parameters[$basePath . 'CallingFeatures.AnonymousCallRejectionEnable'] = $voipLine->anonymous_call_rejection ? 'true' : 'false';
        }

        if ($voipLine->phy_interface) {
            $parameters[$basePath . 'PhyReferenceList'] = $voipLine->phy_interface;
        }

        return $parameters;
    }

    public function provisionCompleteVoiceService(VoiceService $voiceService): array
    {
        $allParameters = [];

        $allParameters = array_merge(
            $allParameters,
            $this->mapVoiceServiceToTR181($voiceService)
        );

        $profiles = $voiceService->sipProfiles()->with('voipLines')->get();
        
        foreach ($profiles as $profile) {
            $allParameters = array_merge(
                $allParameters,
                $this->mapSipProfileToTR181($profile)
            );

            foreach ($profile->voipLines as $line) {
                $allParameters = array_merge(
                    $allParameters,
                    $this->mapVoipLineToTR181($line)
                );
            }
        }

        return $allParameters;
    }
}
