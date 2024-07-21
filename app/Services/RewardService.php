<?php


namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RewardService
{
    function get_nft_metdata($prompt)
    {
        try {
            $apiKey = env('OPENAI_API_KEY');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-3.5-turbo',
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are an artist and a expert in creating NFTs'
                            ],
                            [
                                'role' => 'user',
                                'content' => 'hi!   '
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ],

                        ],
                    ]);

            if ($response->successful()) {
                $response = $response->json();
                return json_decode($response['choices'][0]['message']['content'], true);
            } else {
                echo 'Error: ' . $response->status() . ' ' . $response->body();
                return null;
            }
        } catch (\Throwable $th) {
            return null;
        }


    }


    function get_nft_image_msg_id($prompt)
    {
        try {
            $apiKey = env('MID_API_KEY');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.novita.ai/v3/async/txt2img', [
                        "extra" => [
                            "response_image_type" => "jpeg",
                            "enable_nsfw_detection" => false,
                            "nsfw_detection_level" => 0,
                        ],
                        "request" => [
                            "model_name" => "realisticVisionV51_v51VAE_94301.safetensors",
                            'prompt' => $prompt,
                            "negative_prompt" => "nsfw, bottle,bad face",
                            "width" => 512,
                            "height" => 512,
                            "image_num" => 1,
                            "steps" => 20,
                            "seed" => 123,
                            "clip_skip" => 1,
                            "guidance_scale" => 7.5,
                            "sampler_name" => "Euler a"
                        ]
                    ]);

            if ($response->successful()) {
                $response = $response->json();
                return $response['task_id'];
            } else {
                echo 'Error: ' . $response->status() . ' ' . $response->body();
                return null;
            }
        } catch (\Throwable $th) {
            return null;
        }
    }


    function get_nft_image($msg_id)
    {
        try {
            $retry = true;
            $apiKey = env('MID_API_KEY');
            $max_retries = 15;
            $retries = 0;
            while ($retry && $retries < $max_retries) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->get("https://api.novita.ai/v3/async/task-result?task_id=$msg_id");

                $retries++;

                if (!$response->successful()) {
                    echo 'Error: ' . $response->status() . ' ' . $response->body();
                    return null;
                }
                $response = $response->json();
                $task = $response['task'];
                if ($task['status'] == 'TASK_STATUS_QUEUED' || $task['status'] == 'TASK_STATUS_PROCESSING') {
                    sleep(1);
                    continue;
                } else if ($task['status'] == 'TASK_STATUS_SUCCEED') {
                    $retry = false;
                    return $response['images'][0]['image_url'];
                } else {
                    echo 'Error: ' . $task['reason'];
                    return null;
                }
            }
        } catch (\Throwable $th) {
            return null;
        }
    }


    function get_nft_image_in_one_step($prompt)
    {
        try {
            $apiKey = env('MID_API_KEY');
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.novita.ai/v3/lcm-txt2img', [
                        // "model_name" => "protovisionXLHighFidelity3D_release0630Bakedvae_154359.safetensors",
                        "model_name" => "MAGIFACTORYTShirt_magifactoryTShirtModel_7624.safetensors",
                        'prompt' => $prompt,
                        "negative_prompt" => "",
                        "width" => 1024,
                        "height" => 1024,
                        "image_num" => 1,
                        "steps" => 8,
                        "seed" => -1,
                        "clip_skip" => 1,
                        "guidance_scale" => 3,
                        "sampler_name" => "DPM++ 2S a Karras",

                    ]);

            if ($response->successful()) {
                $response = $response->json();
                return $response['images'][0];
            } else {
                echo 'Error: ' . $response->status() . ' ' . $response->body();
                return null;
            }
        } catch (\Throwable $th) {
            return null;
        }
    }

    function create_new_nft($user, $campaign, $amount)
    {
        $sum_all_amounts = Transaction::where('user_id', $user->id)->sum('amount');
        $prompt = "
        BlockFunders aims to revolutionize the crowdfunding industry by providing a transparent, secure, 
        and globally accessible platform for funding innovative projects using cryptocurrency. 
        In BlockFunder we reward our funders for supporting other projects with NFTs. 
        ### Your task is to generate an NFT with metadata, and You will be provided the following information about the funder and the campaign being funded to create the NFT. 
        User Name: $user->username
        Campaign Name: $campaign->name
        Campaign Description: $campaign->description. 
        Campaign Target Amount: $campaign->target_amount ETH
        User's Donated Amount: $amount ETH
        User's Overall Donated Amount: $sum_all_amounts ETH
        Give me only a JSON as a response to this question that contains the following metadata about the NFT and don't create anything on your own.
        dna, name, description (make it short and creative), image and a list of attributes that has no more than 6 attribute, 
        each attribute should have a trait_type and a color and trait_value";


        $nft_metadata = $this->get_nft_metdata($prompt);
        $max_retries = 3;
        $retry_count = 0;
        while ($retry_count < $max_retries && $nft_metadata === null) {
            sleep(0.5);
            $nft_metadata = $this->get_nft_metdata($prompt);
            $retry_count++;
        }

        if ($nft_metadata === null) {
            return null;
        }

        $nft_metadata_str_prompt = '';
        foreach ($nft_metadata['attributes'] as $attribute) {
            if (key_exists('trait_type', $attribute) and key_exists('trait_value', $attribute)) {
                $nft_metadata_str_prompt .= $attribute['trait_type'] . ': ' . $attribute['trait_value'];
            }
        }

        $name = $nft_metadata['name'];
        $midjourney_prompt = "
        Create a NFT image titled '$name' to represent support for innovative projects through contributions.
        The image should be simple and include the following details:  
        $nft_metadata_str_prompt
        Ensure the overall style is and straightforward, reflecting the essence of cryptocurrency 
        and crowdfunding in a visually captivating way. Please don't use faces";

        // $nft_msd_id = $this->get_nft_image_msg_id($midjourney_prompt);
        // $max_retries = 3;
        // $retry_count = 0;
        // while ($retry_count < $max_retries && $nft_msd_id === null) {
        //     sleep(0.5);
        //     $nft_msd_id = $this->get_nft_image_msg_id($midjourney_prompt);
        //     $retry_count++;
        // }

        // if ($nft_msd_id === null) {
        //     return null;
        // }

        // $nft_image = $this->get_nft_image($nft_msd_id);
        // $max_retries = 2;
        // $retry_count = 0;
        // while ($retry_count < $max_retries && $nft_image === null) {
        //     sleep(0.5);
        //     $nft_image = $this->get_nft_image($nft_msd_id);
        //     $retry_count++;
        // }

        // if ($nft_image === null) {
        //     return null;
        // }
        // $imageContents = Http::get($nft_image)->body();


        $nft_image = $this->get_nft_image_in_one_step($midjourney_prompt);
        $max_retries = 3;
        $retry_count = 0;

        while ($retry_count < $max_retries && $nft_image === null) {
            sleep(0.5);
            $nft_image = $this->get_nft_image_in_one_step($midjourney_prompt);
            $retry_count++;
        }


        $filename = 'nfts_' . uniqid() . '.' . $nft_image['image_type'];
        $imageContents = base64_decode($nft_image['image_file']);

        // Store the image in the 'nfts/images' directory on the default disk
        $path = Storage::put("public/$filename", $imageContents);
        if ($path) {
            $image_url = url('storage/' . $filename);
            $nft_metadata['image'] = $image_url;
        } else {
            // Error handling if the image wasn't saved correctly
            $nft_metadata['image'] = 'Error saving image';
        }
        return $nft_metadata;
    }
}