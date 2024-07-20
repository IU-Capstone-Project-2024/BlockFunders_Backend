<?php


namespace App\Services;

use App\Models\Transaction;
use Illuminate\Support\Facades\Http;


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


    function get_nft_image()
    {
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


        return $nft_metadata;
    }
}