// SPDX-License-Identifier: UNLICENSED
pragma solidity ^0.8.19;

import "hardhat/console.sol";

contract BlockFunders {
    struct Campaign {
        address owner;
        string title;
        string description;
        uint256 targetMoney;
        uint256 deadline;
        uint256 amountCollected;
        string imageUrl;
        address[] donators;
        uint256[] donations;
    }

    mapping(uint256 => Campaign) public campaigns;
    uint256 public numOfCampaigns = 0;

    error DeadlineInvalid();
    error CampaignNotFound();
    error DonationFailed();

    event CampaignCreated(
        uint256 indexed campaignId,
        address owner,
        string title,
        uint256 targetMoney,
        uint256 deadline
    );
    event DonationReceived(
        uint256 indexed campaignId,
        address donor,
        uint256 amount
    );
    event CampaignFunded(uint256 indexed campaignId);

    function createCampaign(
        address _owner,
        string memory _title,
        string memory _description,
        uint256 _targetMoney,
        uint256 _deadline,
        string memory _imageUrl
    ) public returns (uint256) {
        Campaign storage campaign = campaigns[numOfCampaigns];

        if (_deadline <= block.timestamp) {
            revert DeadlineInvalid();
        }

        campaign.owner = _owner;
        campaign.title = _title;
        campaign.description = _description;
        campaign.targetMoney = _targetMoney;
        campaign.deadline = _deadline;
        campaign.imageUrl = _imageUrl;
        campaign.amountCollected = 0;

        emit CampaignCreated(
            numOfCampaigns,
            _owner,
            _title,
            _targetMoney,
            _deadline
        );

        numOfCampaigns++;
        return numOfCampaigns - 1;
    }

    function donateToCampaign(uint256 _campaignId) public payable {
        if (_campaignId >= numOfCampaigns) {
            revert CampaignNotFound();
        }

        uint256 amount = msg.value;
        Campaign storage campaign = campaigns[_campaignId];

        campaign.donators.push(msg.sender);
        campaign.donations.push(amount);

        (bool sent, ) = payable(campaign.owner).call{value: amount}("");
        if (!sent) {
            revert DonationFailed();
        }

        campaign.amountCollected += amount;
        emit DonationReceived(_campaignId, msg.sender, amount);

        if (campaign.amountCollected >= campaign.targetMoney) {
            emit CampaignFunded(_campaignId);
        }
    }

    function getDonators(
        uint256 _campaignId
    ) public view returns (address[] memory, uint256[] memory) {
        if (_campaignId >= numOfCampaigns) {
            revert CampaignNotFound();
        }

        Campaign storage campaign = campaigns[_campaignId];
        return (campaign.donators, campaign.donations);
    }

    function getCampaigns() public view returns (Campaign[] memory) {
        Campaign[] memory allCampaigns = new Campaign[](numOfCampaigns);
        for (uint256 _id = 0; _id < numOfCampaigns; _id++) {
            allCampaigns[_id] = campaigns[_id];
        }
        return allCampaigns;
    }
}
